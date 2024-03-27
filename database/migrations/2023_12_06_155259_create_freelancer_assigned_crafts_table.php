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
        Schema::create('freelancer_assigned_crafts', function (Blueprint $table): void {
            $table->id();
            //cascade on delete so the assigned crafts are deleted when corresponding freelancer is deleted
            $table->foreignId('freelancer_id')->constrained('freelancers')->cascadeOnDelete();
            $table->foreignId('craft_id')->constrained('crafts');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('freelancer_assigned_crafts');
    }
};
