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
        Schema::create('inventory_article_properties', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('tooltip_text')->nullable();
            $table->string('type')->default('string');
            $table->boolean('is_filterable')->default(false);
            $table->boolean('show_in_list')->default(false);
            $table->boolean('is_required')->default(false);
            $table->boolean('is_deletable')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inventory_article_properties');
    }
};
