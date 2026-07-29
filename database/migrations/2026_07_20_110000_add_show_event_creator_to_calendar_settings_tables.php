<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Anzeigeeinstellung "Terminersteller*in" existiert in allen vier
     * Settings-Scopes (Kalender, Kalender-Tagesansicht, Schichtplan,
     * Schichtplan-Tagesansicht), analog zu project_management.
     */
    private const TABLES = [
        'user_calendar_settings',
        'user_daily_view_calendar_settings',
        'user_shift_plan_settings',
        'user_shift_plan_daily_settings',
    ];

    public function up(): void
    {
        foreach (self::TABLES as $tableName) {
            Schema::table($tableName, function (Blueprint $table) use ($tableName): void {
                if (!Schema::hasColumn($tableName, 'show_event_creator')) {
                    $table->boolean('show_event_creator')->default(false)->after('project_management');
                }
            });
        }
    }

    public function down(): void
    {
        foreach (self::TABLES as $tableName) {
            Schema::table($tableName, function (Blueprint $table) use ($tableName): void {
                if (Schema::hasColumn($tableName, 'show_event_creator')) {
                    $table->dropColumn('show_event_creator');
                }
            });
        }
    }
};
