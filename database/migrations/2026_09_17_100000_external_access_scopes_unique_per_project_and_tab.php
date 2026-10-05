<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Dieselbe Person darf denselben (globalen) Tab in mehreren Projekten bekommen. Bisher war
 * [external_access_id, project_tab_id] eindeutig, eine zweite Einladung überschrieb still das
 * Projekt des ersten Scopes. Zusätzlich: Absende-Zeitstempel und Erinnerungs-Marker je Scope.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('external_access_scopes', function (Blueprint $table): void {
            $table->dropUnique(['external_access_id', 'project_tab_id']);
            $table->unique(
                ['external_access_id', 'project_id', 'project_tab_id'],
                'ext_scopes_access_project_tab_unique'
            );
            $table->timestamp('last_submitted_at')->nullable()->after('valid_to');
            $table->timestamp('expiry_reminder_sent_at')->nullable()->after('last_submitted_at');
        });

        Schema::table('external_accesses', function (Blueprint $table): void {
            $table->timestamp('crm_expiry_reminder_sent_at')->nullable()->after('crm_access_expires_at');
        });
    }

    public function down(): void
    {
        Schema::table('external_accesses', function (Blueprint $table): void {
            $table->dropColumn('crm_expiry_reminder_sent_at');
        });

        Schema::table('external_access_scopes', function (Blueprint $table): void {
            $table->dropColumn(['last_submitted_at', 'expiry_reminder_sent_at']);
            $table->dropUnique('ext_scopes_access_project_tab_unique');
            $table->unique(['external_access_id', 'project_tab_id']);
        });
    }
};
