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
        Schema::create('money_source_tasks', function (Blueprint $table): void {
            $table->id();
            $table->bigInteger('money_source_id');
            $table->string('name');
            $table->text('description')->nullable();
            $table->dateTime('deadline')->nullable();
            $table->bigInteger('creator');
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
        Schema::dropIfExists('money_source_tasks');
    }
};
