<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * department_user hatte nur den Primärschlüssel. Seit Teams Raumadmins sein können (department_room),
     * prüfen Policies und Raum-Payloads die Mitgliedschaft bei fast jedem Aufruf.
     */
    public function up(): void
    {
        $indexedColumns = collect(Schema::getIndexes('department_user'))->pluck('columns')->all();

        Schema::table('department_user', function (Blueprint $table) use ($indexedColumns): void {
            if (!in_array(['user_id', 'department_id'], $indexedColumns, true)) {
                $table->index(['user_id', 'department_id']);
            }
            if (!in_array(['department_id'], $indexedColumns, true)) {
                $table->index('department_id');
            }
        });
    }

    public function down(): void
    {
        $indexedColumns = collect(Schema::getIndexes('department_user'))->pluck('columns')->all();

        Schema::table('department_user', function (Blueprint $table) use ($indexedColumns): void {
            if (in_array(['user_id', 'department_id'], $indexedColumns, true)) {
                $table->dropIndex(['user_id', 'department_id']);
            }
            if (in_array(['department_id'], $indexedColumns, true)) {
                $table->dropIndex(['department_id']);
            }
        });
    }
};
