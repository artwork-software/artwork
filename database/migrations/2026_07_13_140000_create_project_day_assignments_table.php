<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('project_day_assignments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->string('employable_type');
            $table->unsignedBigInteger('employable_id');
            $table->date('date');
            $table->string('type', 10);
            $table->uuid('group_id');
            $table->boolean('is_full_period')->default(false);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('superseded_by_shift_id')->nullable()->constrained('shifts')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            // Kein UNIQUE über (project, employable, date, type): Soft-Deletes (supersedete
            // Zeilen mit deleted_at) würden die Wiederanlage am selben Tag blockieren bzw.
            // MySQL-NULL-Semantik die Aktiv-Zeilen gar nicht schützen — Eindeutigkeit
            // aktiver Zeilen wird im ProjectDayAssignmentService durchgesetzt.
            $table->index(['employable_type', 'employable_id', 'date'], 'pda_employable_date_index');
            $table->index(['project_id', 'date']);
            $table->index('group_id');
            $table->index('superseded_by_shift_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_day_assignments');
    }
};
