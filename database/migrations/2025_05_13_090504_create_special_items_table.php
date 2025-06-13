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
        Schema::create('special_items', function (Blueprint $table) {
            $table->id();
            $table->morphs('issuable'); // issuable_type, issuable_id
            $table->string('name');
            $table->integer('quantity');
            $table->text('description')->nullable();
            $table->foreignId('inventory_category_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('inventory_sub_category_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('special_items');
    }
};
