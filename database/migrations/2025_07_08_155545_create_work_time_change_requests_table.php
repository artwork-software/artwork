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
        Schema::create('work_time_change_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')
                ->constrained('users')
                ->onDelete('cascade');
            $table->time('request_start_time');
            $table->time('request_end_time');
            $table->foreignId('shift_id')
                ->nullable()
                ->constrained('shifts')
                ->nullOnDelete();
            $table->foreignId('craft_id')
                ->nullable()
                ->constrained('crafts')
                ->nullOnDelete();
            $table->string('status')->default('pending'); // e.g., pending, approved, declined
            $table->text('request_comment')->nullable();
            $table->text('decline_comment')->nullable();
            $table->foreignId('requested_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->foreignId('approved_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->foreignId('declined_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('work_time_change_requests');
    }
};
