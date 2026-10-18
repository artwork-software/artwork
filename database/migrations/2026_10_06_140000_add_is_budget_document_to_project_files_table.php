<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Query\Builder;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Kennzeichnet Budget-Dokumente (Upload über Budget-Informationen mit Freigabeliste). Sie sind nur für
 * die freigegebenen Personen und Admins sichtbar – auch in „Alle Dokumente“, im Druck und beim Download.
 * Backfill: Dateien ohne Tab mit mindestens zwei Freigaben. Beim Hochladen wird die hochladende Person
 * immer eingetragen; Dateien mit nur einem Eintrag sind von Uploads aus Dokumente-Komponenten
 * (Seitenleiste, gelöschte öffentliche Tabs) nicht zu unterscheiden und bleiben ungekennzeichnet.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('project_files', 'is_budget_document')) {
            Schema::table('project_files', function (Blueprint $table): void {
                $table->boolean('is_budget_document')->default(false)->after('tab_id');
            });
        }

        DB::table('project_files')
            ->whereNull('tab_id')
            ->whereIn('id', function (Builder $query): void {
                $query->select('project_file_id')
                    ->from('project_file_user')
                    ->groupBy('project_file_id')
                    ->havingRaw('COUNT(DISTINCT user_id) >= 2');
            })
            ->update(['is_budget_document' => true]);
    }

    public function down(): void
    {
        Schema::table('project_files', function (Blueprint $table): void {
            $table->dropColumn('is_budget_document');
        });
    }
};
