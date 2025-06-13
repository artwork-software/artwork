<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('issuable_inventory_article', function (Blueprint $table) {
            $table->id();
            $table->morphs('issuable'); // issuable_id, issuable_type
            $table->foreignId('inventory_article_id')->constrained()->cascadeOnDelete();
            $table->integer('quantity')->default(1);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('issuable_inventory_article');
    }
};
