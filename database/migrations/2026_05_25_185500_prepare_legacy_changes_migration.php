<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Prepares the database for moving Antonrom's `changes` data into Spatie's
 * `activity_log` table.
 *
 * 1. Adds a `legacy_change_id` column on `activity_log` so the artisan
 *    `changes:migrate-to-activity-log` command (invoked from `artwork:update`)
 *    can write idempotently.
 * 2. Renames `changes` → `changes_legacy` so the original table is preserved
 *    as a rollback fallback while the new code path runs against Spatie.
 *
 * Both steps run unconditionally on every fresh `migrate`, so deployment
 * pipelines do not need a multi-step sequence. The data copy itself happens
 * in `UpdateArtwork::migrateChangesHistoryToActivityLog()`.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (
            Schema::hasTable('activity_log')
            && !Schema::hasColumn('activity_log', 'legacy_change_id')
        ) {
            Schema::table('activity_log', function (Blueprint $table): void {
                $table->unsignedBigInteger('legacy_change_id')->nullable()->after('id');
                $table->unique('legacy_change_id');
            });
        }

        if (Schema::hasTable('changes') && !Schema::hasTable('changes_legacy')) {
            Schema::rename('changes', 'changes_legacy');
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('changes_legacy') && !Schema::hasTable('changes')) {
            Schema::rename('changes_legacy', 'changes');
        }

        if (
            Schema::hasTable('activity_log')
            && Schema::hasColumn('activity_log', 'legacy_change_id')
        ) {
            Schema::table('activity_log', function (Blueprint $table): void {
                $table->dropUnique(['legacy_change_id']);
                $table->dropColumn('legacy_change_id');
            });
        }
    }
};
