<?php

namespace Artwork\Modules\Inventory\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class TypeNumberGenerator
{
    public const PREFIX = 'aw';

    public static function generateExternalId(): string
    {
        return self::PREFIX . '-' . (string) Str::ulid();
    }

    public static function generateDetailExternalId(string $mainExternalId, int $detailNumber): string
    {
        return $mainExternalId . '-' . $detailNumber;
    }

    /**
     * Generates the next sequential inventory number (e.g. "00001", "00042").
     * Numbers are never reused: a persistent counter remembers the last issued number,
     * so permanently deleting the newest article does not hand its number out again.
     * lockForUpdate only has an effect inside a transaction — when the caller
     * has none (e.g. the model saving hook during imports), open one ourselves.
     */
    public static function generateInventoryNumber(): string
    {
        if (DB::transactionLevel() === 0) {
            return DB::transaction(static fn (): string => self::generateInventoryNumber());
        }

        $counter = DB::table('inventory_number_counter')->where('id', 1)->lockForUpdate()->first();
        $lastIssued = (int) ($counter->last_number ?? 0);
        // Manuell vergebene/importierte höhere Nummern nie überholen
        $currentMax = (int) DB::table('inventory_articles')
            ->max(DB::raw('CAST(inventory_number AS UNSIGNED)'));
        $nextNumber = max($lastIssued, $currentMax) + 1;

        DB::table('inventory_number_counter')->updateOrInsert(['id' => 1], ['last_number' => $nextNumber]);

        return str_pad((string) $nextNumber, 5, '0', STR_PAD_LEFT);
    }

    public static function generateDetailInventoryNumber(string $mainInventoryNumber, int $detailNumber): string
    {
        return $mainInventoryNumber . '-' . str_pad((string) $detailNumber, 3, '0', STR_PAD_LEFT);
    }
}
