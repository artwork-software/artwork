<?php

namespace Artwork\Modules\ExternalAccess\Services;

use Artwork\Core\FileHandling\Naming\StoredFileName;
use Artwork\Core\FileHandling\ServerUploadLimit;
use Artwork\Core\FileHandling\Upload\SafeUploadFile;
use Artwork\Modules\Crm\Services\CrmPropertyFileService;
use Artwork\Modules\ExternalAccess\Models\ExternalPendingFieldChange;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

/**
 * Dateien, die externe Personen in „Meine Daten“ für Upload-Eigenschaften einreichen.
 *
 * Die Datei landet NICHT am Kontakt, sondern vorläufig auf der privaten local-Disk unter
 * {@see self::DIRECTORY}; die Einreichung merkt sich Pfad und Originalnamen. Erst die Freigabe kopiert
 * sie nach crm-property-files. Ablehnen, Ersetzen durch eine neuere Einreichung oder die Freigabe
 * selbst entfernen die vorläufige Datei wieder.
 *
 * Gespeicherte Werte einer Upload-Änderung (new_value, JSON):
 * - Datei: ['kind' => 'upload', 'path' => 'external-crm-submissions/<hex>.<ext>', 'name' => 'Original.pdf']
 * - Entfernen: ['kind' => 'remove']
 * Alles andere (ein Text-Pfad aus der Zeit vor dem Datei-Feld) ist Altbestand und wird nie übernommen.
 */
class ExternalSelfEditFileService
{
    public const DIRECTORY = 'external-crm-submissions';

    public const DISK = 'local';

    public const KIND_UPLOAD = 'upload';

    public const KIND_REMOVE = 'remove';

    private const PENDING_PATH_PATTERN = '#^external-crm-submissions/[a-f0-9]{32}(\.[a-z0-9]{1,16})?$#';

    public function __construct(
        private readonly ExternalAccessSettingsResolver $settingsResolver,
        private readonly CrmPropertyFileService $propertyFileService,
    ) {
    }

    /**
     * Gleicher hausweiter Schalter wie für Dokument-Komponenten im freigegebenen Tab.
     */
    public function isUploadEnabled(): bool
    {
        return $this->settingsResolver->isFileUploadEnabled();
    }

    /**
     * @throws AuthorizationException
     */
    public function assertUploadEnabled(): void
    {
        if (!$this->isUploadEnabled()) {
            throw new AuthorizationException(__('File upload for external accesses is disabled.'));
        }
    }

    /**
     * Typ- und Größenregeln wie beim internen Upload von Eigenschaftsdateien.
     *
     * @return array{accept: string, max_kilobytes: int}
     */
    public function constraints(): array
    {
        $extensions = explode(',', CrmPropertyFileService::ALLOWED_EXTENSIONS);

        return [
            'accept' => implode(',', array_map(static fn (string $extension): string => '.' . $extension, $extensions)),
            'max_kilobytes' => CrmPropertyFileService::MAX_KILOBYTES,
        ];
    }

    /**
     * Prüft und speichert die Datei vorläufig; liefert den Wert für die Einreichung.
     *
     * @return array{kind: string, path: string, name: string}
     * @throws ValidationException
     */
    public function storePending(UploadedFile $file, string $errorKey, string $label): array
    {
        $this->validate($file, $errorKey, $label);

        $path = $file->storeAs(self::DIRECTORY, StoredFileName::forUpload($file), self::DISK);
        if (!is_string($path) || $path === '') {
            throw ValidationException::withMessages([$errorKey => __('validation.file_upload.failed')]);
        }

        return [
            'kind' => self::KIND_UPLOAD,
            'path' => $path,
            'name' => $this->displayNameOf($file),
        ];
    }

    /**
     * @return array{kind: string}
     */
    public function removal(): array
    {
        return ['kind' => self::KIND_REMOVE];
    }

    /**
     * Strukturierter Wert einer Upload-Änderung oder null (Altbestand / unbekannte Form).
     *
     * @return array{kind: string, path?: string, name?: string}|null
     */
    public function parse(mixed $value): ?array
    {
        if (!is_array($value)) {
            return null;
        }

        $kind = $value['kind'] ?? null;
        if ($kind === self::KIND_REMOVE) {
            return ['kind' => self::KIND_REMOVE];
        }

        if ($kind !== self::KIND_UPLOAD) {
            return null;
        }

        $path = $this->normalisePendingPath($value['path'] ?? null);
        if ($path === null) {
            return null;
        }

        $name = is_string($value['name'] ?? null) && $value['name'] !== '' ? $value['name'] : basename($path);

        return ['kind' => self::KIND_UPLOAD, 'path' => $path, 'name' => $name];
    }

    /**
     * Pfad der vorläufigen Datei eines Änderungswerts, wenn sie noch auf der Disk liegt.
     */
    public function existingPendingPathOf(mixed $value): ?string
    {
        $parsed = $this->parse($value);
        $path = $parsed['path'] ?? null;

        return $path !== null && Storage::disk(self::DISK)->exists($path) ? $path : null;
    }

    /**
     * Kopiert die vorläufige Datei nach crm-property-files; die vorläufige bleibt bis nach dem Commit
     * liegen (siehe deletePending), damit ein Rollback nichts verliert.
     */
    public function promote(string $pendingPath): string
    {
        $target = CrmPropertyFileService::DIRECTORY . '/' . basename($pendingPath);
        Storage::disk(self::DISK)->copy($pendingPath, $target);

        return $target;
    }

    public function deletePending(mixed $value): void
    {
        $path = $this->parse($value)['path'] ?? null;

        if ($path !== null) {
            Storage::disk(self::DISK)->delete($path);
        }
    }

    /**
     * Vorläufige Dateien aller übergebenen Änderungen löschen (Ablehnen, Ersetzen).
     *
     * @param iterable<ExternalPendingFieldChange> $changes
     */
    public function deletePendingOf(iterable $changes): void
    {
        foreach ($changes as $change) {
            $this->deletePending($change->new_value);
        }
    }

    /**
     * Bei einem Rollback der Freigabe schon nach crm-property-files kopierte Dateien wieder entfernen.
     *
     * @param iterable<string> $paths
     */
    public function deleteCopiedPropertyFiles(iterable $paths): void
    {
        $this->propertyFileService->deleteMany($paths);
    }

    /**
     * Datei einer Upload-Eigenschaft am Kontakt entfernen (nach dem Commit der Freigabe).
     */
    public function deletePropertyFile(mixed $storedValue): void
    {
        $this->propertyFileService->delete($storedValue);
    }

    /**
     * Anzeigename der gespeicherten Datei am Kontakt (intern gibt es nur den gespeicherten Pfad).
     */
    public function storedFileNameOf(mixed $storedValue): ?string
    {
        $path = $this->propertyFileService->normalisePath($storedValue);

        return $path !== null ? basename($path) : null;
    }

    public function normalisePendingPath(mixed $raw): ?string
    {
        if (!is_string($raw) || preg_match(self::PENDING_PATH_PATTERN, $raw) !== 1) {
            return null;
        }

        return $raw;
    }

    /**
     * @throws ValidationException
     */
    private function validate(UploadedFile $file, string $errorKey, string $label): void
    {
        // Server-Limit (upload_max_filesize/post_max_size) gerissen: verständliche Meldung statt "failed"
        if (!$file->isValid() && in_array($file->getError(), [UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE], true)) {
            throw ValidationException::withMessages([
                $errorKey => __('validation.file_upload.server_max_size', ['size' => ServerUploadLimit::inMegabytes()]),
            ]);
        }

        $validator = Validator::make(
            ['file' => $file],
            ['file' => [
                'file',
                'mimes:' . CrmPropertyFileService::ALLOWED_EXTENSIONS,
                'max:' . CrmPropertyFileService::MAX_KILOBYTES,
                new SafeUploadFile(),
            ]],
            [],
            ['file' => $label],
        );

        if ($validator->fails()) {
            throw ValidationException::withMessages([$errorKey => $validator->errors()->first('file')]);
        }
    }

    private function displayNameOf(UploadedFile $file): string
    {
        $name = trim(str_replace(["\r", "\n", "\0", '/', '\\'], ' ', $file->getClientOriginalName()));

        return mb_substr($name !== '' ? $name : $file->hashName(), 0, 255);
    }
}
