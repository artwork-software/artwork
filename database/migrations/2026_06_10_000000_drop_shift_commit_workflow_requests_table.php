<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Entfernt das tote Parallel-System "ShiftCommitWorkflowRequests".
 *
 * Der Festschreibungs-Workflow läuft über ShiftPlanRequest (pro Gewerk pro KW);
 * diese Tabelle wurde von keiner erreichbaren UI mehr befüllt und ihr
 * Approve-Pfad war seit der craftId-Erweiterung von commitShiftsByDate defekt.
 * shift_commit_workflow_users bleibt bestehen (steuert die Genehmiger-Sicht).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('shift_commit_workflow_requests');
    }

    public function down(): void
    {
        Schema::create('shift_commit_workflow_requests', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('requested_by_id')->constrained('users')->cascadeOnDelete();
            $table->date('start_date');
            $table->date('end_date');
            $table->foreignId('approved_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('declined_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status')->default('pending');
            $table->text('reason')->nullable();
            $table->timestamps();
        });
    }
};
