<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('workflow_rule_violations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workflow_rule_id')
                ->constrained('workflow_rules')
                ->onDelete('cascade');
            $table->string('subject_type');
            $table->unsignedBigInteger('subject_id');
            $table->date('violation_date');
            $table->json('violation_data')->nullable();
            $table->enum('severity', ['low', 'medium', 'high'])->default('medium');
            $table->enum('status', ['pending', 'acknowledged', 'resolved', 'dismissed'])->default('pending');
            $table->timestamp('resolved_at')->nullable();
            $table->unsignedBigInteger('resolved_by')->nullable();
            $table->timestamps();

            $table->index(['workflow_rule_id', 'violation_date'], 'workflow_rule_violation_index');
            $table->index(['subject_type', 'subject_id', 'violation_date'], 'workflow_rule_violation_subject_index');
            $table->index(['status', 'violation_date'], 'workflow_rule_violation_status_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('workflow_rule_violations');
    }
};
