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
        Schema::create('shift_rule_violations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shift_rule_id')->constrained('shift_rules')->onDelete('cascade');
            $table->foreignId('shift_id')->constrained('shifts')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->date('violation_date');
            $table->json('violation_data')->nullable();
            $table->enum('severity', ['warning', 'error'])->default('warning');
            $table->enum('status', ['active', 'resolved', 'ignored'])->default('active');
            $table->timestamp('resolved_at')->nullable();
            $table->foreignId('resolved_by')->nullable()->constrained('users');
            $table->timestamps();
            
            $table->index(['shift_id', 'violation_date']);
            $table->index(['user_id', 'violation_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shift_rule_violations');
    }
};
