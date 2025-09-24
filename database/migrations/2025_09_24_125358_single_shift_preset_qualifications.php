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
        Schema::create('single_shift_preset_qualifications', function (Blueprint $table) {
            $table->id();

            // Spalten zuerst
            $table->unsignedBigInteger('single_shift_preset_id');
            $table->unsignedBigInteger('shift_qualification_id');

            $table->integer('quantity')->default(0);
            $table->timestamps();

            // Eindeutige Kombination
            $table->unique(
                ['single_shift_preset_id', 'shift_qualification_id'],
                'sspq_unique'
            );

            // Kurze, benannte Foreign Keys (<= 64 Zeichen)
            $table->foreign('single_shift_preset_id', 'sspq_preset_fk')
                ->references('id')->on('single_shift_presets')
                ->cascadeOnDelete();

            $table->foreign('shift_qualification_id', 'sspq_qual_fk')
                ->references('id')->on('shift_qualifications')
                ->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('single_shift_preset_qualifications');
    }
};
