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
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('checklist_has_projects')->default(false);
            $table->boolean('checklist_no_projects')->default(false);
            $table->boolean('checklist_private_checklists')->default(false);
            $table->boolean('checklist_no_private_checklists')->default(false);
            $table->boolean('checklist_completed_tasks')->default(false);
            $table->boolean('checklist_show_without_tasks')->default(true);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('checklist_has_projects');
            $table->dropColumn('checklist_no_projects');
            $table->dropColumn('checklist_private_checklists');
            $table->dropColumn('checklist_no_private_checklists');
            $table->dropColumn('checklist_completed_tasks');
            $table->dropColumn('checklist_show_without_tasks');
        });
    }
};
