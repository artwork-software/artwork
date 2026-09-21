<?php

namespace Artwork\Core\Console\Commands;

use Artwork\Core\FileHandling\StoredFilePath;
use Artwork\Modules\Crm\Enums\CrmPropertyTypeEnum;
use Artwork\Modules\Crm\Models\CrmPropertyValue;
use Artwork\Modules\ExternalIssue\Models\ExternalIssueFile;
use Artwork\Modules\InternalIssue\Models\InternalIssueFile;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Throwable;

/**
 * Verschiebt CRM-Eigenschaftsdateien, Materialausgabe-Anhänge und Ausgabe-PDFs von der public- auf die
 * local-Disk und normalisiert die DB-Pfade (StoredFilePath). Idempotent; Fehler an einzelnen Dateien
 * werden gemeldet und übersprungen. Bis zum Lauf liefert PrivateFileResponse noch von "public" aus.
 */
class MovePublicFilesToPrivateDiskCommand extends Command
{
    /**
     * Erlaubte Verzeichnisse je Tabelle; "materialausgabe" ist ein frühes Verzeichnis der internen Materialausgabe.
     */
    private const CRM_PROPERTY_FILE_DIRECTORIES = ['crm-property-files'];

    private const INTERNAL_ISSUE_DIRECTORIES = ['material-issue', 'materialausgabe'];

    private const EXTERNAL_ISSUE_DIRECTORIES = ['external_material_issues'];

    protected $signature = 'artwork:security:move-public-files {--dry-run : Nur anzeigen, nichts verschieben}';

    protected $description = 'Move CRM property files and material issue attachments/PDFs from the public disk '
        . 'to the private local disk and normalise the stored paths';

    private int $moved = 0;

    private int $alreadyPrivate = 0;

    private int $missing = 0;

    private int $normalised = 0;

    private int $failed = 0;

    public function handle(): int
    {
        // Die Instanz kann im selben Prozess mehrfach laufen (Octane, Tests).
        $this->moved = $this->alreadyPrivate = $this->missing = $this->normalised = $this->failed = 0;
        $dryRun = (bool) $this->option('dry-run');

        if (Schema::hasTable('crm_property_values') && Schema::hasTable('crm_properties')) {
            CrmPropertyValue::query()
                ->whereNotNull('value')
                ->whereHas('property', fn ($query) => $query->where('type', CrmPropertyTypeEnum::UPLOAD->value))
                ->chunkById(200, function ($values) use ($dryRun): void {
                    foreach ($values as $value) {
                        $this->processSafely($value, 'value', self::CRM_PROPERTY_FILE_DIRECTORIES, $dryRun);
                    }
                });
        }

        if (Schema::hasTable('internal_issue_files')) {
            InternalIssueFile::query()->chunkById(200, function ($files) use ($dryRun): void {
                foreach ($files as $file) {
                    $this->processSafely($file, 'file_path', self::INTERNAL_ISSUE_DIRECTORIES, $dryRun);
                }
            });
        }

        if (Schema::hasTable('external_issue_files')) {
            ExternalIssueFile::query()->chunkById(200, function ($files) use ($dryRun): void {
                foreach ($files as $file) {
                    $this->processSafely($file, 'file_path', self::EXTERNAL_ISSUE_DIRECTORIES, $dryRun);
                }
            });
        }

        $this->info(sprintf(
            '%sMoved: %d, already private: %d, paths normalised: %d, missing on both disks: %d, failed: %d',
            $dryRun ? '[dry-run] ' : '',
            $this->moved,
            $this->alreadyPrivate,
            $this->normalised,
            $this->missing,
            $this->failed
        ));

        if ($this->failed > 0) {
            $this->warn('Some files could not be moved; they stay readable from the public disk '
                . '(fallback) - re-run "artwork:security:move-public-files" after fixing the cause.');
        }

        return self::SUCCESS;
    }

    /**
     * @param list<string> $allowedDirectories
     */
    private function processSafely(Model $model, string $attribute, array $allowedDirectories, bool $dryRun): void
    {
        try {
            $this->process($model, $attribute, $allowedDirectories, $dryRun);
        } catch (Throwable $exception) {
            $this->failed++;
            $this->warn(sprintf(
                'Failed to move %s (%s #%d): %s',
                (string) $model->getAttribute($attribute),
                $model::class,
                $model->getKey(),
                $exception->getMessage()
            ));
        }
    }

    /**
     * @param list<string> $allowedDirectories
     */
    private function process(Model $model, string $attribute, array $allowedDirectories, bool $dryRun): void
    {
        $raw = (string) $model->getAttribute($attribute);
        $path = StoredFilePath::normaliseWithin($raw, $allowedDirectories);

        if ($path === null) {
            return;
        }

        $local = Storage::disk('local');
        $public = Storage::disk('public');

        if ($local->fileExists($path)) {
            $this->alreadyPrivate++;

            if ($public->fileExists($path) && !$dryRun) {
                $public->delete($path);
            }
        } elseif ($public->fileExists($path)) {
            if (!$dryRun) {
                $stream = $public->readStream($path);

                if ($stream === null) {
                    $this->failed++;
                    $this->warn(sprintf(
                        'Cannot read %s on public disk (%s #%d)',
                        $path,
                        $model::class,
                        $model->getKey()
                    ));

                    return;
                }

                try {
                    $written = $local->writeStream($path, $stream);
                } finally {
                    if (is_resource($stream)) {
                        fclose($stream);
                    }
                }

                // Nur löschen, wenn die private Kopie wirklich da ist - sonst bleibt der public-Fallback
                if ($written === false || !$local->fileExists($path)) {
                    $this->failed++;
                    $this->warn(sprintf(
                        'Could not write %s to local disk (%s #%d) - left on public disk',
                        $path,
                        $model::class,
                        $model->getKey()
                    ));

                    return;
                }

                $public->delete($path);
            }

            $this->moved++;
        } else {
            $this->missing++;
            $this->warn(sprintf('File missing on both disks: %s (%s #%d)', $path, $model::class, $model->getKey()));
        }

        if ($path !== $raw) {
            $this->normalised++;

            if (!$dryRun) {
                $model->forceFill([$attribute => $path])->save();
            }
        }
    }
}
