<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('inventory_article_filter_presets', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('inventory_category_id')->nullable();
            $table->unsignedBigInteger('inventory_sub_category_id')->nullable();

            $table->string('name', 80);
            $table->boolean('is_default')->default(false);

            $table->json('filters')->nullable();
            $table->json('tag_ids')->nullable();

            $table->timestamps();

            $table->index(['user_id']);
            $table->index(['is_default']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_article_filter_presets');
    }
};
