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
        Schema::create('craft_shift_qualification', function (Blueprint $table) {
            $table->id();

            // Beziehungen
            $table->unsignedBigInteger('craft_id');
            $table->unsignedBigInteger('shift_qualification_id');

            $table->timestamps();

            // Unique-Kombination (jeder Craft hat eine Qualifikation nur einmal)
            $table->unique(['craft_id', 'shift_qualification_id'], 'uq_craft_shift_qual');

            // Foreign Keys
            $table->foreign('craft_id', 'fk_csq_craft')
                ->references('id')
                ->on('crafts')
                ->onDelete('cascade');

            $table->foreign('shift_qualification_id', 'fk_csq_shift_qual')
                ->references('id')
                ->on('shift_qualifications')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('craft_shift_qualification');
    }
};
