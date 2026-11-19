<?php

namespace Artwork\Modules\Crm\Services;

use Artwork\Core\FileHandling\Naming\DownloadFileName;
use Artwork\Core\FileHandling\StoredFilePath;
use Artwork\Modules\Crm\Enums\CrmPropertyTypeEnum;
use Artwork\Modules\Crm\Models\CrmContact;
use Artwork\Modules\Crm\Models\CrmPropertyValue;
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

    private const PROFILE_IMAGE_PATTERN = '#^crm-profile-images/[A-Za-z0-9]{1,64}(\.[A-Za-z0-9]{1,16})?$#';

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

    /**
     * Anzeigename einer Eigenschaftsdatei (Originalname beim Upload oder neuer Name beim Umbenennen).
     * Pfadtrenner und Steuerzeichen fliegen raus; fehlt die Endung der gespeicherten Datei, wird sie
     * angehängt, damit der Download weiter mit dem passenden Programm aufgeht. Null = kein brauchbarer Name.
     */
    public function displayName(mixed $name, string $storedPath): ?string
    {
        $name = DownloadFileName::sanitize($name);
        $name = $name === null ? '' : rtrim($name, '. ');

        if ($name === '') {
            return null;
        }

        $extension = strtolower(pathinfo($storedPath, PATHINFO_EXTENSION));

        if ($extension !== '' && strtolower(pathinfo($name, PATHINFO_EXTENSION)) !== $extension) {
            $name .= '.' . $extension;
        }

        return $name;
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
     * Gültige Dateipfade aller Upload-Eigenschaften eines Kontakts. Pfade, auf die noch ein anderer Kontakt
     * zeigt (Altbestand zusammengeführter Duplikate), bleiben außen vor.
     *
     * @return list<string>
     */
    public function collectPathsOfContact(CrmContact $contact): array
    {
        $rawPaths = $contact->propertyValues()
            ->whereHas(
                'property',
                static fn (Builder $query) => $query->where('type', CrmPropertyTypeEnum::UPLOAD->value)
            )
            ->whereNotNull('value')
            ->pluck('value');

        $sharedPaths = $rawPaths->isEmpty() ? [] : CrmPropertyValue::query()
            ->where('crm_contact_id', '!=', $contact->id)
            ->whereIn('value', $rawPaths->all())
            ->pluck('value')
            ->all();

        return $this->normalisePaths($rawPaths->reject(
            static fn (mixed $value): bool => in_array($value, $sharedPaths, true)
        ));
    }

    /**
     * Gültige Dateipfade der Upload-Eigenschaften unter den gegebenen Eigenschaften, optional nur eines Kontakts.
     * Gedacht für den Moment, bevor die Werte gelöscht werden (Eigenschaft, Gruppe, Typwechsel).
     *
     * @param array<int, int> $propertyIds
     * @return list<string>
     */
    public function collectPathsOfProperties(array $propertyIds, ?int $contactId = null): array
    {
        if ($propertyIds === []) {
            return [];
        }

        return $this->normalisePaths(
            CrmPropertyValue::query()
                ->whereIn('crm_property_id', $propertyIds)
                ->whereHas(
                    'property',
                    static fn (Builder $query) => $query->where('type', CrmPropertyTypeEnum::UPLOAD->value)
                )
                ->when($contactId !== null, static fn (Builder $query) => $query->where('crm_contact_id', $contactId))
                ->whereNotNull('value')
                ->pluck('value')
        );
    }

    /**
     * Löscht ein hochgeladenes Profilbild, sobald kein Kontakt mehr darauf zeigt. Gespiegelte Kontakte
     * verweisen auf Bilder ihrer Quelle (Nutzer*in, Freelancer*in); die liegen woanders und bleiben unberührt.
     */
    public function deleteProfileImage(mixed $rawPath, ?int $exceptContactId = null): void
    {
        $path = StoredFilePath::normalise($rawPath);

        if (!is_string($path) || preg_match(self::PROFILE_IMAGE_PATTERN, $path) !== 1) {
            return;
        }

        $stillUsed = CrmContact::withTrashed()
            ->where('profile_image', $rawPath)
            ->when($exceptContactId !== null, static fn (Builder $query) => $query->whereKeyNot($exceptContactId))
            ->exists();

        if (!$stillUsed) {
            Storage::disk('public')->delete($path);
        }
    }

    /**
     * @param iterable<mixed> $rawPaths
     * @return list<string>
     */
    private function normalisePaths(iterable $rawPaths): array
    {
        return collect($rawPaths)
            ->map(fn (mixed $value): ?string => $this->normalisePath($value))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }
}
