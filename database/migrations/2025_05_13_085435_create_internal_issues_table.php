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
        Schema::create('internal_issues', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->foreignId('project_id')->nullable()->constrained()->nullOnDelete();
            $table->date('start_date');
            $table->time('start_time')->default('00:00');
            $table->date('end_date');
            $table->time('end_time')->default('23:59');
            $table->foreignId('room_id')->nullable()->constrained()->nullOnDelete();
            $table->text('notes')->nullable();
            $table->boolean('special_items_done')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('internal_issues');
    }
};
