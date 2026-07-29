<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Kosten-Erfassung im BI-Modul (Plan/Ist je scope-Zeile), analog zum Umsatz:
 * Total-Wert, "nicht relevant"-Flag und Herkunfts-Stempel für übernommene
 * Vorschläge aus dem Budget (budget_expense) bzw. Sage (sage).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bi_project_data', function (Blueprint $table): void {
            if (!Schema::hasColumn('bi_project_data', 'costs_total')) {
                $table->decimal('costs_total', 12, 2)->nullable()->after('revenue_total');
            }
            if (!Schema::hasColumn('bi_project_data', 'costs_not_applicable')) {
                $table->boolean('costs_not_applicable')->default(false)->after('revenue_not_applicable');
            }
            if (!Schema::hasColumn('bi_project_data', 'costs_source')) {
                $table->string('costs_source')->nullable()->after('revenue_source_synced_at');
            }
            if (!Schema::hasColumn('bi_project_data', 'costs_source_synced_at')) {
                $table->timestamp('costs_source_synced_at')->nullable()->after('costs_source');
            }
        });
    }

    public function down(): void
    {
        Schema::table('bi_project_data', function (Blueprint $table): void {
            $table->dropColumn([
                'costs_total',
                'costs_not_applicable',
                'costs_source',
                'costs_source_synced_at',
            ]);
        });
    }
};
