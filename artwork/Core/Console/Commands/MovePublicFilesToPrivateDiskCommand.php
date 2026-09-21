<?php

namespace Artwork\Core\Console\Commands;

use Artwork\Modules\Crm\Enums\CrmPropertyTypeEnum;
use Artwork\Modules\Crm\Models\CrmPropertyValue;
use Artwork\Modules\ExternalIssue\Models\ExternalIssueFile;
use Artwork\Modules\InternalIssue\Models\InternalIssueFile;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

/**
 * Sicherheits-Audit 21.09.2026, Abschnitt F: CRM-Eigenschaftsdateien, Materialausgabe-Anhänge und
 * generierte Ausgabe-PDFs lagen auf der public-Disk (ohne Login unter /storage lesbar). Neue Dateien
 * landen auf "local"; dieses Command holt den Altbestand nach und normalisiert die DB-Pfade
 * (relativer Pfad ohne "/storage/"- oder "public/"-Präfix).
 *
 * Idempotent: Was bereits auf "local" liegt, wird nur noch von "public" entfernt; fehlende Dateien
 * werden gemeldet, nicht angefasst. Läuft aus artwork:update heraus.
 */
class MovePublicFilesToPrivateDiskCommand extends Command
{
    protected $signature = 'artwork:security:move-public-files {--dry-run : Nur anzeigen, nichts verschieben}';

    protected $description = 'Move CRM property files and material issue attachments/PDFs from the public disk '
        . 'to the private local disk and normalise the stored paths';

    private int $moved = 0;

    private int $alreadyPrivate = 0;

    private int $missing = 0;

    private int $normalised = 0;

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');

        if (Schema::hasTable('crm_property_values') && Schema::hasTable('crm_properties')) {
            CrmPropertyValue::query()
                ->whereNotNull('value')
                ->whereHas('property', fn ($query) => $query->where('type', CrmPropertyTypeEnum::UPLOAD->value))
                ->chunkById(200, function ($values) use ($dryRun): void {
                    foreach ($values as $value) {
                        $this->process($value, 'value', 'crm-property-files', $dryRun);
                    }
                });
        }

        if (Schema::hasTable('internal_issue_files')) {
            InternalIssueFile::query()->chunkById(200, function ($files) use ($dryRun): void {
                foreach ($files as $file) {
                    $this->process($file, 'file_path', 'material-issue', $dryRun);
                }
            });
        }

        if (Schema::hasTable('external_issue_files')) {
            ExternalIssueFile::query()->chunkById(200, function ($files) use ($dryRun): void {
                foreach ($files as $file) {
                    $this->process($file, 'file_path', 'external_material_issues', $dryRun);
                }
            });
        }

        $this->info(sprintf(
            '%sMoved: %d, already private: %d, paths normalised: %d, missing on both disks: %d',
            $dryRun ? '[dry-run] ' : '',
            $this->moved,
            $this->alreadyPrivate,
            $this->normalised,
            $this->missing
        ));

        return self::SUCCESS;
    }

    private function process(Model $model, string $attribute, string $expectedDirectory, bool $dryRun): void
    {
        $raw = (string) $model->getAttribute($attribute);
        $path = $this->normalisePath($raw);

        // Nur Pfade des erwarteten Verzeichnisses (keine Fremdpfade, keine Traversal-Muster)
        if ($path === null || !str_starts_with($path, $expectedDirectory . '/')) {
            return;
        }

        $local = Storage::disk('local');
        $public = Storage::disk('public');

        if ($local->exists($path)) {
            $this->alreadyPrivate++;

            if ($public->exists($path) && !$dryRun) {
                $public->delete($path);
            }
        } elseif ($public->exists($path)) {
            if (!$dryRun) {
                $stream = $public->readStream($path);

                if ($stream === null) {
                    $this->warn(sprintf(
                        'Cannot read %s on public disk (%s #%d)',
                        $path,
                        $model::class,
                        $model->getKey()
                    ));

                    return;
                }

                $local->writeStream($path, $stream);

                if (is_resource($stream)) {
                    fclose($stream);
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

    /**
     * "/storage/x/y", "storage/x/y", "public/x/y" → "x/y"; Traversal und absolute Pfade werden verworfen.
     */
    private function normalisePath(string $raw): ?string
    {
        $path = trim($raw);

        if ($path === '') {
            return null;
        }

        $path = preg_replace('#^(https?://[^/]+)?/?(storage/|public/)?#', '', $path) ?? $path;
        $path = ltrim($path, '/');

        if ($path === '' || str_contains($path, '..') || str_contains($path, "\0")) {
            return null;
        }

        return $path;
    }
}
