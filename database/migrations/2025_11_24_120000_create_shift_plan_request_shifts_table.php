<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('shift_plan_request_shifts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shift_plan_request_id')
                ->constrained('shift_plan_requests')
                ->cascadeOnDelete();

            $table->foreignId('shift_id')
                ->constrained('shifts')
                ->cascadeOnDelete();

            // optional: Snapshot der Schicht-Zustände zum Zeitpunkt der Anfrage
            $table->json('snapshot')->nullable();

            $table->timestamps();

            $table->unique(['shift_plan_request_id', 'shift_id']);
            $table->index(['shift_plan_request_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shift_plan_request_shifts');
    }
};

