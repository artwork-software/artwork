<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('workflow_rule_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workflow_rule_id')
                ->constrained('workflow_rules')
                ->onDelete('cascade');
            $table->string('subject_type');
            $table->unsignedBigInteger('subject_id');
            $table->timestamp('assigned_at');
            $table->unsignedBigInteger('assigned_by')->nullable();
            $table->timestamps();

            $table->index(['workflow_rule_id', 'subject_type', 'subject_id'], 'workflow_rule_assignment_index');
            $table->index(['subject_type', 'subject_id']);
            $table->unique(['workflow_rule_id', 'subject_type', 'subject_id'], 'workflow_rule_assignment_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('workflow_rule_assignments');
    }
};
