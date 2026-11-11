<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Adresse je Raum für Veranstaltungs- und Künstlerplan. Damit lassen sich auch externe Orte
 * (Hotels, Restaurants, Gastspielorte) als Raum anlegen und mit Adresse ausgeben.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rooms', function (Blueprint $table): void {
            $table->string('street')->nullable()->after('capacity');
            $table->string('zip_code', 20)->nullable()->after('street');
            $table->string('city')->nullable()->after('zip_code');
        });
    }

    public function down(): void
    {
        Schema::table('rooms', function (Blueprint $table): void {
            $table->dropColumn(['street', 'zip_code', 'city']);
        });
    }
};
