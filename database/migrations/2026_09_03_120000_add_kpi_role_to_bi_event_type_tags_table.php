<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Welche BI-Tags die Kennzahlen "Vorstellungen" und "Veranstaltungstage" steuern,
 * hängt nicht mehr am (umbenennbaren) deutschen Tag-Namen, sondern an einer
 * expliziten Rolle. Bestehende Tags werden einmalig über den Namen zugeordnet.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('bi_event_type_tags', 'kpi_role')) {
            Schema::table('bi_event_type_tags', function (Blueprint $table): void {
                $table->string('kpi_role', 32)->nullable()->unique()->after('color');
            });
        }

        foreach (['performance' => 'vorstellung', 'event_day' => 'veranstaltungstag'] as $role => $needle) {
            $alreadyAssigned = DB::table('bi_event_type_tags')->where('kpi_role', $role)->exists();
            if ($alreadyAssigned) {
                continue;
            }

            $id = DB::table('bi_event_type_tags')
                ->whereNull('kpi_role')
                ->where(function ($query) use ($needle): void {
                    $query->whereRaw('LOWER(name_de) = ?', [$needle])
                        ->orWhereRaw('LOWER(name) = ?', [$needle]);
                })
                ->orderBy('id')
                ->value('id');

            if ($id !== null) {
                DB::table('bi_event_type_tags')->where('id', $id)->update(['kpi_role' => $role]);
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('bi_event_type_tags', 'kpi_role')) {
            Schema::table('bi_event_type_tags', function (Blueprint $table): void {
                $table->dropUnique(['kpi_role']);
                $table->dropColumn('kpi_role');
            });
        }
    }
};
