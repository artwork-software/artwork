<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Sage-Client prüft TLS-Zertifikate standardmäßig; der Schalter erlaubt das bewusste Abschalten
 * (z. B. Sage-Server mit selbstsigniertem Zertifikat im Intranet).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('sage_api_settings', 'verify_ssl')) {
            return;
        }

        Schema::table('sage_api_settings', function (Blueprint $table): void {
            $table->boolean('verify_ssl')->nullable()->default(true)->after('enabled');
        });
    }

    public function down(): void
    {
        if (!Schema::hasColumn('sage_api_settings', 'verify_ssl')) {
            return;
        }

        Schema::table('sage_api_settings', function (Blueprint $table): void {
            $table->dropColumn('verify_ssl');
        });
    }
};
