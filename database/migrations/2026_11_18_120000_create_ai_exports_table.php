<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * PDF-Exporte aus der KI-Bar (core-ai-event-stats-exports). Die Datei liegt unter
 * storage/app/pdf/ai/{file}; die Zeile haelt fest, wem sie gehoert und wann sie verfaellt.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_exports', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('file')->unique();
            $table->string('title');
            $table->unsignedInteger('row_count');
            $table->timestamp('expires_at')->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_exports');
    }
};
