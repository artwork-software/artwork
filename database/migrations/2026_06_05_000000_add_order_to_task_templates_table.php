<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Aufgaben innerhalb einer Checklisten-Vorlage können per Drag&Drop sortiert werden.
     * Dafür wird eine explizite Sortierreihenfolge pro Task-Template benötigt.
     */
    public function up(): void
    {
        Schema::table('task_templates', function (Blueprint $table): void {
            $table->unsignedInteger('order')->nullable()->after('checklist_template_id');
        });
    }

    public function down(): void
    {
        Schema::table('task_templates', function (Blueprint $table): void {
            $table->dropColumn('order');
        });
    }
};
