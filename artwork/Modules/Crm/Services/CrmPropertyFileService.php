<?php

namespace Artwork\Modules\Crm\Services;

use Artwork\Core\FileHandling\StoredFilePath;
use Artwork\Modules\Crm\Enums\CrmPropertyTypeEnum;
use Artwork\Modules\Crm\Models\CrmContact;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Storage;

/**
 * Dateien von Upload-Eigenschaften der CRM-Kontakte: Pfadprüfung und Löschen von den Disks.
 * Neue Dateien liegen auf der privaten local-Disk, Altbestand ggf. noch auf public.
 */
readonly class CrmPropertyFileService
{
    public const DIRECTORY = 'crm-property-files';

    /**
     * Erlaubte Endungen für Eigenschaftsdateien (intern und über externe Einreichungen).
     */
    public const ALLOWED_EXTENSIONS = 'pdf,jpg,jpeg,png,webp,doc,docx,xls,xlsx,csv,txt';

    /**
     * Größenlimit für Eigenschaftsdateien in Kilobyte (10 MB).
     */
    public const MAX_KILOBYTES = 10240;

    /**
     * Gespeicherte Pfade: StoredFileName (32 hex) oder Laravel-hashName (40 alnum) aus dem Altbestand.
     */
    private const PATH_PATTERN = '#^crm-property-files/[A-Za-z0-9]{1,64}(\.[A-Za-z0-9]{1,16})?$#';

    private const DISKS = ['local', 'public'];

    /**
     * Normalisierter Pfad, wenn der Wert auf eine Eigenschaftsdatei zeigt, sonst null.
     */
    public function normalisePath(mixed $raw): ?string
    {
        $path = StoredFilePath::normalise($raw);

        if (!is_string($path) || preg_match(self::PATH_PATTERN, $path) !== 1) {
            return null;
        }

        return $path;
    }

    public function delete(mixed $raw): void
    {
        $path = $this->normalisePath($raw);

        if ($path === null) {
            return;
        }

        foreach (self::DISKS as $disk) {
            Storage::disk($disk)->delete($path);
        }
    }

    /**
     * @param iterable<mixed> $rawPaths
     */
    public function deleteMany(iterable $rawPaths): void
    {
        foreach ($rawPaths as $rawPath) {
            $this->delete($rawPath);
        }
    }

    /**
     * Gültige Dateipfade aller Upload-Eigenschaften eines Kontakts.
     *
     * @return list<string>
     */
    public function collectPathsOfContact(CrmContact $contact): array
    {
        return $contact->propertyValues()
            ->whereHas(
                'property',
                static fn (Builder $query) => $query->where('type', CrmPropertyTypeEnum::UPLOAD->value)
            )
            ->pluck('value')
            ->map(fn (mixed $value): ?string => $this->normalisePath($value))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }
}
