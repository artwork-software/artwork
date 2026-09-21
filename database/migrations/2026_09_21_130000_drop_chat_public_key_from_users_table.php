<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Sicherheits-Audit 21.09.2026 (G): Das RSA-Keypair des Chats wurde nie zum Ver-/Entschlüsseln
 * benutzt (Nachrichten laufen serverseitig über Crypt::encryptString); der private Schlüssel lag
 * unverschlüsselt im localStorage. Frontend, Route und Spalte werden entfernt.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('users', 'chat_public_key')) {
            return;
        }

        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn('chat_public_key');
        });
    }

    public function down(): void
    {
        if (Schema::hasColumn('users', 'chat_public_key')) {
            return;
        }

        Schema::table('users', function (Blueprint $table): void {
            $table->longText('chat_public_key')->nullable();
        });
    }
};
