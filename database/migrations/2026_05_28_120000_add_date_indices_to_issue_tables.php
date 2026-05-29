<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * B9: Add indexes on the date columns used by inventory planning's
 * overlap query (`start_date`/`end_date` and `issue_date`/`return_date`).
 *
 * The planning page queries:
 *   WHERE start_date <= :rangeEnd AND end_date >= :rangeStart
 * Without an index on these columns MySQL falls back to a full table scan.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::table('internal_issues', function (Blueprint $table) {
            $table->index('start_date', 'internal_issues_start_date_idx');
            $table->index('end_date', 'internal_issues_end_date_idx');
        });

        Schema::table('external_issues', function (Blueprint $table) {
            $table->index('issue_date', 'external_issues_issue_date_idx');
            $table->index('return_date', 'external_issues_return_date_idx');
        });
    }

    public function down(): void
    {
        Schema::table('internal_issues', function (Blueprint $table) {
            $table->dropIndex('internal_issues_start_date_idx');
            $table->dropIndex('internal_issues_end_date_idx');
        });

        Schema::table('external_issues', function (Blueprint $table) {
            $table->dropIndex('external_issues_issue_date_idx');
            $table->dropIndex('external_issues_return_date_idx');
        });
    }
};
