<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('inventory_article_filter_states', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('inventory_category_id')->nullable();
            $table->unsignedBigInteger('inventory_sub_category_id')->nullable();

            $table->json('filters')->nullable();
            $table->json('tag_ids')->nullable();

            $table->timestamps();

            $table->unique(['user_id', 'inventory_category_id', 'inventory_sub_category_id'], 'inv_article_filter_state_unique');

            $table->index(['user_id']);
            $table->index(['inventory_category_id']);
            $table->index(['inventory_sub_category_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_article_filter_states');
    }
};
