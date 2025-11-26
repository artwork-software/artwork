<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_article_inventory_tag', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('inventory_article_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('inventory_tag_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->timestamps();

            $table->unique(['inventory_article_id', 'inventory_tag_id'], 'inventory_article_tag_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_article_inventory_tag');
    }
};
