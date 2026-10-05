<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Neue Einladungsquelle "crm_contact" (Einladung eines bestehenden Kontakts von dessen Detailseite).
 * Die Spalte ist ein MySQL/MariaDB-ENUM; andere Treiber (sqlite in Tests) kennen keine ENUM-Prüfung.
 */
return new class extends Migration
{
    private const SOURCES_NEW = "'crm_index','project_tab','crm_contact'";
    private const SOURCES_OLD = "'crm_index','project_tab'";

    public function up(): void
    {
        if (!in_array(Schema::getConnection()->getDriverName(), ['mysql', 'mariadb'], true)) {
            return;
        }

        DB::statement('ALTER TABLE `external_invitations` MODIFY `source` ENUM(' . self::SOURCES_NEW . ') NOT NULL');
    }

    public function down(): void
    {
        if (!in_array(Schema::getConnection()->getDriverName(), ['mysql', 'mariadb'], true)) {
            return;
        }

        DB::table('external_invitations')->where('source', 'crm_contact')->update(['source' => 'crm_index']);
        DB::statement('ALTER TABLE `external_invitations` MODIFY `source` ENUM(' . self::SOURCES_OLD . ') NOT NULL');
    }
};
