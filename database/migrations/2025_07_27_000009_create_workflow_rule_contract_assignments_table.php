<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('workflow_rule_contract_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workflow_rule_id')->constrained('workflow_rules')->onDelete('cascade');
            $table->foreignId('contract_id')->constrained('user_contracts')->onDelete('cascade');
            $table->timestamps();

            $table->unique(['workflow_rule_id', 'contract_id'], 'rule_contract_unique');
            $table->index(['workflow_rule_id'], 'rule_assignments_rule_idx');
            $table->index(['contract_id'], 'rule_assignments_contract_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('workflow_rule_contract_assignments');
    }
};