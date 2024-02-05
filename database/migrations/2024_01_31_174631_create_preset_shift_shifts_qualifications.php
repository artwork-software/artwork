<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::create('preset_shift_shifts_qualifications', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('preset_shift_id')->constrained('preset_shifts')->cascadeOnDelete();
            $table->unsignedBigInteger('shift_qualification_id');
            $table->foreign('shift_qualification_id', 'shift_qualification_id_foreign')
                ->references('id')
                ->on('shift_qualifications');
            $table->unsignedSmallInteger('value')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('preset_shift_shifts_qualifications');
    }
};
