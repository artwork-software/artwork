<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * DP-18 Stufe 2: Überstunden-Frist je Vertrag.
     */
    private array $tables = ['user_contracts', 'user_contract_assigns'];

    public function up(): void
    {
        foreach ($this->tables as $tableName) {
            Schema::table($tableName, function (Blueprint $table): void {
                $table->boolean('overtime_payout_active')->default(false)->after('annual_vacation_days');
                $table->unsignedInteger('overtime_deadline_days')->default(0)->after('overtime_payout_active');
            });
        }
    }

    public function down(): void
    {
        foreach ($this->tables as $tableName) {
            Schema::table($tableName, function (Blueprint $table): void {
                $table->dropColumn(['overtime_payout_active', 'overtime_deadline_days']);
            });
        }
    }
};
