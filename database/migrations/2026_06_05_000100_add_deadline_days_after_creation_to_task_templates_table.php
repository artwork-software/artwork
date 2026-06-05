<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Statt eines festen Datums kann pro Task-Template hinterlegt werden, wie viele Tage
     * nach Erstellung der Checkliste die Deadline der erzeugten Aufgabe liegen soll.
     */
    public function up(): void
    {
        Schema::table('task_templates', function (Blueprint $table): void {
            $table->unsignedInteger('deadline_days_after_creation')->nullable()->after('order');
        });
    }

    public function down(): void
    {
        Schema::table('task_templates', function (Blueprint $table): void {
            $table->dropColumn('deadline_days_after_creation');
        });
    }
};
