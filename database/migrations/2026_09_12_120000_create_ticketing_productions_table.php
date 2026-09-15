<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Wie ein Projekt im Ticketshop auftritt: Anzeigename, Text, Bild, gewährte Ermäßigungen.
        // production_id entsteht mit der ersten Freigabe eines Termins; bis dahin ist die Zeile ein Entwurf.
        Schema::create('ticketing_productions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('project_id')->unique()->constrained('projects')->cascadeOnDelete();
            $table->uuid('production_id')->nullable();
            $table->string('title', 160)->nullable();
            $table->text('description')->nullable();
            $table->json('reduction_type_ids')->nullable();
            $table->string('hero_path')->nullable();
            $table->timestamp('hero_synced_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ticketing_productions');
    }
};
