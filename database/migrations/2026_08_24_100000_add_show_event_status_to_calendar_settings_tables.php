<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Anzeigeeinstellung "Terminstatus ausgeschrieben" existiert wie die übrigen
     * Kachel-Einstellungen in allen vier Settings-Scopes, weil das geteilte
     * Settings-Modal das Feld aus jedem Scope mitsendet — angezeigt wird der
     * Status aber nur in den Kalender-Kacheln. Default false: neue Zeile ist opt-in.
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
                if (!Schema::hasColumn($tableName, 'show_event_status')) {
                    $table->boolean('show_event_status')->default(false)->after('show_event_admission');
                }
            });
        }
    }

    public function down(): void
    {
        foreach (self::TABLES as $tableName) {
            Schema::table($tableName, function (Blueprint $table) use ($tableName): void {
                if (Schema::hasColumn($tableName, 'show_event_status')) {
                    $table->dropColumn('show_event_status');
                }
            });
        }
    }
};
