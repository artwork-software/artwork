<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * api_access_tokens hielt eine Klartextkopie des vollständigen Passport-JWT. Sie existierte nur für
 * zwei Zwecke: das nachträgliche Wiederanzeigen des Tokens in der Oberfläche und den Reverse-Lookup
 * in der Log-Middleware. Beides ist entfallen — die Oberfläche zeigt den Token nur noch einmalig bei
 * der Erstellung, die Middleware nimmt die Token-Identität aus dem Auth-Guard.
 *
 * Alle übrigen Token-Metadaten (Name, Ablauf, revoked, Scopes) liegen ohnehin in oauth_access_tokens.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('api_access_tokens');
    }

    public function down(): void
    {
        Schema::create('api_access_tokens', function (Blueprint $table): void {
            $table->id();
            $table->text('passport_token_id');
            // Struktur wird wiederhergestellt, Inhalte bewusst nicht: Klartext-Tokens kehren nicht zurück.
            $table->text('access_token');
            $table->timestamps();
        });
    }
};
