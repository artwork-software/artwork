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
     * Must be called inside a DB transaction for race-condition safety.
     */
    public static function generateInventoryNumber(): string
    {
        $maxNumber = (int) DB::table('inventory_articles')
            ->lockForUpdate()
            ->max(DB::raw('CAST(inventory_number AS UNSIGNED)'));

        return str_pad((string) ($maxNumber + 1), 5, '0', STR_PAD_LEFT);
    }

    public static function generateDetailInventoryNumber(string $mainInventoryNumber, int $detailNumber): string
    {
        return $mainInventoryNumber . '-' . str_pad((string) $detailNumber, 3, '0', STR_PAD_LEFT);
    }
}
