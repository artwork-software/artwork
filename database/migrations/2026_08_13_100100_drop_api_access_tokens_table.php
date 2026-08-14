<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

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
