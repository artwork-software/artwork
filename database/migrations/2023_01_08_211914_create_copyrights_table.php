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
        Schema::create('copyrights', function (Blueprint $table): void {
            $table->id();
            $table->boolean('own_copyright');
            $table->boolean('live_music');
            $table->string('collecting_society_id');
            $table->enum('law_size', ['small', 'big']);
            $table->foreignId('project_id');
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
        Schema::dropIfExists('copyright');
    }
};
