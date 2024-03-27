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
        Schema::create('columns', function (Blueprint $table): void {
            $table->id();
            $table->bigInteger('table_id');
            $table->string('name')->nullable();
            $table->string('subName')->nullable();
            $table->string('type')->nullable();
            $table->integer('linked_first_column')->nullable();
            $table->integer('linked_second_column')->nullable();
            $table->string('color')->default('whiteColumn');
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
        Schema::dropIfExists('columns');
    }
};
