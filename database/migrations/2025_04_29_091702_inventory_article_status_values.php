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
        Schema::create('inventory_article_status_values', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inventory_article_status_id')
                ->constrained('inventory_article_statuses')
                ->name('inv_art_stat_val_fk')
                ->cascadeOnDelete();
            $table->foreignId('inventory_article_id')->constrained('inventory_articles')->cascadeOnDelete();
            $table->string('value');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inventory_article_status_values');
    }
};
