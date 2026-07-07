<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('user_contracts', function (Blueprint $table): void {
            $table->boolean('overtime_rule_active')->default(false)->after('compensation_period');
            $table->integer('overtime_compensation_period')->nullable()->after('overtime_rule_active');
        });

        Schema::table('user_contract_assigns', function (Blueprint $table): void {
            $table->boolean('overtime_rule_active')->default(false)->after('compensation_period');
            $table->integer('overtime_compensation_period')->nullable()->after('overtime_rule_active');
        });
    }

    public function down(): void
    {
        Schema::table('user_contracts', function (Blueprint $table): void {
            $table->dropColumn(['overtime_rule_active', 'overtime_compensation_period']);
        });

        Schema::table('user_contract_assigns', function (Blueprint $table): void {
            $table->dropColumn(['overtime_rule_active', 'overtime_compensation_period']);
        });
    }
};
