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
        Schema::dropIfExists('project_project_headlines');
        Schema::dropIfExists('project_headlines');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::create('project_headlines', function (Blueprint $table): void {
            $table->id();
            $table->string('name')->nullable();
            $table->integer('order');
            $table->timestamps();
        });
        Schema::create('project_project_headlines', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('project_id');
            $table->unsignedBigInteger('project_headline_id');
            $table->text('text')->nullable();
            $table->timestamps();
        });
    }
};
