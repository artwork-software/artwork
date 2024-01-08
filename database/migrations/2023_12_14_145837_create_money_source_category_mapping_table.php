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
        Schema::create('money_source_category_mappings', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('money_source_id')->constrained('money_sources');
            $table->foreignId('money_source_category_id')->constrained('money_source_categories');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('money_source_category_mappings');
    }
};
