<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sage_assigned_data', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('column_cell_id')
                ->constrained('column_sub_position_row')
                ->noActionOnDelete();
            $table->unsignedBigInteger('sage_id');
            $table->unsignedBigInteger('tan');
            $table->string('kreditor');
            $table->string('buchungstext');
            $table->float('buchungsbetrag');
            $table->string('belegnummer');
            $table->string('belegdatum');
            $table->string('sa_kto');
            $table->string('kst_traeger');
            $table->string('kst_stelle');
            $table->string('buchungsdatum');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sage_assigned_data');
    }
};
