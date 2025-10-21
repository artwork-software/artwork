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
        Schema::create('product_basket_articles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_basket_id')->constrained()->onDelete('cascade');
            $table->foreignId('article_id')->constrained()->on('inventory_articles')->onDelete('cascade');
            $table->integer('quantity')->default(1);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_basket_articles');
    }
};
