<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const string INDEX = 'day_serviceables_unique_assignment';

    /**
     * Doppelklicks im Tagesdienst-Modus legten dieselbe Zuweisung (Tagesdienst, Tag, Person) mehrfach an.
     * Duplikate entfernen (älteste Zeile bleibt) und künftig per Unique-Index verhindern.
     */
    public function up(): void
    {
        $duplicateIds = DB::table('day_serviceables as duplicate')
            ->join('day_serviceables as original', function ($join): void {
                $join->on('original.day_service_id', '=', 'duplicate.day_service_id')
                    ->on('original.date', '=', 'duplicate.date')
                    ->on('original.day_serviceable_type', '=', 'duplicate.day_serviceable_type')
                    ->on('original.day_serviceable_id', '=', 'duplicate.day_serviceable_id')
                    ->on('original.id', '<', 'duplicate.id');
            })
            ->distinct()
            ->pluck('duplicate.id');

        foreach ($duplicateIds->chunk(1000) as $chunk) {
            DB::table('day_serviceables')->whereIn('id', $chunk->all())->delete();
        }

        if ($this->hasIndex()) {
            return;
        }

        Schema::table('day_serviceables', function (Blueprint $table): void {
            $table->unique(
                ['day_service_id', 'date', 'day_serviceable_type', 'day_serviceable_id'],
                self::INDEX
            );
        });
    }

    public function down(): void
    {
        if (!$this->hasIndex()) {
            return;
        }

        Schema::table('day_serviceables', function (Blueprint $table): void {
            $table->dropUnique(self::INDEX);
        });
    }

    private function hasIndex(): bool
    {
        return collect(Schema::getIndexes('day_serviceables'))->contains('name', self::INDEX);
    }
};
