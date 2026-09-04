<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Manuelle Verstöße ohne Regel ("Sonstiges"): shift_rule_id wird nullable, dazu ein eigener Titel.
 * hasColumn-Guards, damit die Migration auf Ständen mit/ohne Squash gleichermaßen läuft.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('shift_rule_violations')) {
            return;
        }

        if (!Schema::hasColumn('shift_rule_violations', 'title')) {
            Schema::table('shift_rule_violations', function (Blueprint $table): void {
                $table->string('title', 120)->nullable()->after('user_id');
            });
        }

        if (Schema::hasColumn('shift_rule_violations', 'shift_rule_id')) {
            Schema::table('shift_rule_violations', function (Blueprint $table): void {
                $table->dropForeign(['shift_rule_id']);
            });

            // Kein doctrine/dbal im Projekt -> Spalte per Statement auf NULL-fähig umstellen (MySQL/MariaDB).
            if (in_array(DB::getDriverName(), ['mysql', 'mariadb'], true)) {
                DB::statement('ALTER TABLE `shift_rule_violations` MODIFY `shift_rule_id` BIGINT UNSIGNED NULL');
            }

            Schema::table('shift_rule_violations', function (Blueprint $table): void {
                $table->foreign('shift_rule_id')->references('id')->on('shift_rules')->cascadeOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('shift_rule_violations')) {
            return;
        }

        if (Schema::hasColumn('shift_rule_violations', 'title')) {
            Schema::table('shift_rule_violations', function (Blueprint $table): void {
                $table->dropColumn('title');
            });
        }
    }
};
