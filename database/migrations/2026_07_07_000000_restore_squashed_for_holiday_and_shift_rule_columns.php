<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Repair migration: the 2026_04_16_* migrations were deleted by the schema-dump
// squash (ARTWORK-335) before all production instances had run them. The columns
// exist in database/schema/mysql-schema.sql (fresh installs) but are missing on
// instances whose last deploy was before 2026-04-16. Guarded so it is a no-op
// where the columns already exist.
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('compensation_day_offs', 'for_holiday')) {
            Schema::table('compensation_day_offs', function (Blueprint $table): void {
                $table->boolean('for_holiday')->default(false)->after('reason');
            });
        }

        if (!Schema::hasColumn('shift_rules', 'default_compensation_days')) {
            Schema::table('shift_rules', function (Blueprint $table): void {
                $table->decimal('default_compensation_days', 3, 1)->nullable()->after('warning_color');
            });
        }

        if (!Schema::hasColumn('shift_rules', 'default_compensation_deadline_days')) {
            Schema::table('shift_rules', function (Blueprint $table): void {
                $table->unsignedInteger('default_compensation_deadline_days')
                    ->nullable()
                    ->after('default_compensation_days');
            });
        }
    }

    public function down(): void
    {
    }
};
