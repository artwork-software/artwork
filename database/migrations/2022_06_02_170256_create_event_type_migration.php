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
        Schema::create('event_types', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('svg_name');
            $table->boolean('project_mandatory');
            $table->boolean('individual_name');
            $table->string('abbreviation');
            $table->boolean('relevant_for_shift')->default(false);
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
        Schema::dropIfExists('event_type_migration');
    }
};
