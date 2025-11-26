<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_tag_user', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('inventory_tag_id')
                ->constrained('inventory_tags')
                ->cascadeOnDelete();

            $table->foreignId('user_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->timestamps();

            $table->unique(['inventory_tag_id', 'user_id'], 'inventory_tag_user_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_tag_user');
    }
};
