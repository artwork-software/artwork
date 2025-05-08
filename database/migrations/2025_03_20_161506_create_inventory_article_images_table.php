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
        Schema::create('inventory_article_images', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inventory_article_id')->constrained('inventory_articles')->cascadeOnDelete();
            $table->string('image');
            $table->boolean('is_main_image')->default(false);
            $table->unsignedInteger('order')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inventory_article_images');
    }
};
