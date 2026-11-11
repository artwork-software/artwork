<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

/**
 * Passport 13 fasst diese Tabelle nicht mehr an; der Personal-Access-Client wird ueber
 * grant_types gefunden (core-passport-schema-upgrade). Siehe vendor/laravel/passport/UPGRADE.md,
 * Abschnitt "Personal Access Client Table and Model Removal".
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('oauth_personal_access_clients');
    }

    /**
     * Kein Rueckweg: Passport 13 benutzt die Tabelle nicht mehr, ein Wiederanlegen waere eine
     * leere Huelle ohne Inhalt und ohne Leser.
     */
    public function down(): void
    {
    }
};
