<?php

namespace Artwork\Modules\Change\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use stdClass;
use Throwable;

class MigrateChangesToActivityLogCommand extends Command
{
    protected $signature = 'changes:migrate-to-activity-log
        {--chunk=1000 : Chunk size for iteration over the legacy changes table}';

    protected $description = 'Copy rows from the archived `changes_legacy` table (Antonrom) into Spatie `activity_log`. Idempotent via `legacy_change_id`. Invoked from `artwork:update`.';

    public function handle(): int
    {
        if (!Schema::hasTable('changes_legacy')) {
            $this->info('No `changes_legacy` table found — nothing to migrate.');
            return self::SUCCESS;
        }

        if (!Schema::hasColumn('activity_log', 'legacy_change_id')) {
            $this->error('Column `activity_log.legacy_change_id` is missing. Run `php artisan migrate` first.');
            return self::FAILURE;
        }

        $chunkSize = max(1, (int) $this->option('chunk'));

        $total = (int) DB::table('changes_legacy')->count();

        if ($total === 0) {
            $this->info('`changes_legacy` is empty — nothing to migrate.');
            return self::SUCCESS;
        }

        $alreadyMigrated = (int) DB::table('activity_log')->whereNotNull('legacy_change_id')->count();

        if ($alreadyMigrated >= $total) {
            $this->info(sprintf(
                'All %d legacy change rows already migrated to activity_log — skipping.',
                $total
            ));
            return self::SUCCESS;
        }

        $this->info(sprintf(
            'Migrating up to %d legacy change rows (%d already migrated, chunk=%d).',
            $total,
            $alreadyMigrated,
            $chunkSize
        ));

        $bar = $this->output->createProgressBar($total);
        $bar->start();

        $migrated = 0;
        $skippedAlready = 0;
        $skippedBadJson = 0;
        $errors = 0;

        DB::table('changes_legacy')->orderBy('id')->chunkById($chunkSize, function ($rows) use (
            $bar,
            &$migrated,
            &$skippedAlready,
            &$skippedBadJson,
            &$errors,
        ): void {
            $existingIds = DB::table('activity_log')
                ->whereIn('legacy_change_id', $rows->pluck('id'))
                ->pluck('legacy_change_id')
                ->all();
            $existingIdsLookup = array_flip($existingIds);

            $toInsert = [];

            foreach ($rows as $row) {
                if (isset($existingIdsLookup[$row->id])) {
                    $skippedAlready++;
                    $bar->advance();
                    continue;
                }

                try {
                    $properties = $this->decodeProperties($row);
                } catch (Throwable $e) {
                    $skippedBadJson++;
                    Log::channel('single')->warning(
                        '[changes:migrate-to-activity-log] skipped row id=' . $row->id .
                        ' bad JSON: ' . $e->getMessage(),
                        ['raw' => $row->changes]
                    );
                    $bar->advance();
                    continue;
                }

                $event = $row->change_type ?: 'updated';
                $createdAt = $row->created_at;

                $toInsert[] = [
                    'legacy_change_id' => $row->id,
                    'log_name' => 'legacy',
                    'description' => sprintf('%s on %s', $event, $row->model_type ?? 'unknown'),
                    'subject_type' => $row->model_type,
                    'subject_id' => $row->model_id,
                    'event' => $event,
                    'causer_type' => $row->changer_type,
                    'causer_id' => $row->changer_id,
                    'properties' => json_encode($properties),
                    'batch_uuid' => null,
                    'created_at' => $createdAt,
                    'updated_at' => $createdAt,
                ];

                $bar->advance();
            }

            if ($toInsert === []) {
                return;
            }

            try {
                DB::transaction(function () use ($toInsert): void {
                    DB::table('activity_log')->insert($toInsert);
                });
                $migrated += count($toInsert);
            } catch (Throwable $e) {
                $errors += count($toInsert);
                Log::channel('single')->error(
                    '[changes:migrate-to-activity-log] chunk insert failed: ' . $e->getMessage(),
                    ['rows' => array_column($toInsert, 'legacy_change_id')]
                );
            }
        });

        $bar->finish();
        $this->newLine();

        $this->info(sprintf(
            'Done. migrated=%d, skipped_already=%d, skipped_bad_json=%d, errors=%d',
            $migrated,
            $skippedAlready,
            $skippedBadJson,
            $errors
        ));

        return $errors > 0 ? self::FAILURE : self::SUCCESS;
    }

    /**
     * Decode the Antonrom `changes` column into the same array shape that
     * ChangeBuilder writes today, so Resources/frontend consumers see a
     * consistent payload regardless of whether the row was migrated or
     * freshly written through Spatie.
     */
    private function decodeProperties(stdClass $row): array
    {
        $raw = $row->changes ?? null;

        if ($raw === null || $raw === '') {
            return [];
        }

        if (is_array($raw)) {
            return $raw;
        }

        $decoded = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);

        if (!is_array($decoded)) {
            return ['raw' => $decoded];
        }

        return $decoded;
    }
}
