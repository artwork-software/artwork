<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Seit wann die E-Mail eines Typs aus ist: Beim Wiedereinschalten werden nur Meldungen aus dieser Zeit
 * als zusammengefasst markiert. updated_at taugt dafür nicht – jede Push-/Häufigkeitsänderung und jede
 * Sammeländerung verschiebt es. Bestand: die letzte Änderung als beste Schätzung.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('notification_settings', 'email_disabled_at')) {
            Schema::table('notification_settings', function (Blueprint $table): void {
                $table->timestamp('email_disabled_at')->nullable()->after('enabled_email');
            });
        }

        DB::table('notification_settings')
            ->where('enabled_email', false)
            ->whereNull('email_disabled_at')
            ->update(['email_disabled_at' => DB::raw('COALESCE(updated_at, created_at, ' . DB::getPdo()->quote(now()->toDateTimeString()) . ')')]);
    }

    public function down(): void
    {
        if (Schema::hasColumn('notification_settings', 'email_disabled_at')) {
            Schema::table('notification_settings', function (Blueprint $table): void {
                $table->dropColumn('email_disabled_at');
            });
        }
    }
};
