<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Künstler*innen (System-Kontaktart "artist") bekommen eine E-Mail-Spalte, damit externe Zugänge
 * eindeutig an den Kontakt gebunden und beim erneuten Einladen per E-Mail wiedergefunden werden.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('artists', 'email')) {
            return;
        }

        Schema::table('artists', function (Blueprint $table): void {
            $table->string('email')->nullable()->after('last_name')->index();
        });
    }

    public function down(): void
    {
        if (!Schema::hasColumn('artists', 'email')) {
            return;
        }

        Schema::table('artists', function (Blueprint $table): void {
            $table->dropIndex(['email']);
            $table->dropColumn('email');
        });
    }
};
