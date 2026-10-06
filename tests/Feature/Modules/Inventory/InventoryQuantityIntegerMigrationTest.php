<?php

namespace Tests\Feature\Modules\Inventory;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

/**
 * Umrechnung der Inventarmengen auf ganze Zahlen (Migration 2026_10_05_120000). Geprüft auf
 * temporären Tabellen mit den alten Spaltentypen – ein ALTER TABLE der echten Tabellen würde die
 * Test-Transaktion implizit committen. CREATE/DROP TEMPORARY TABLE tun das nicht.
 */
final class InventoryQuantityIntegerMigrationTest extends FeatureTestCase
{
    private const QUANTITY_TABLE = 'tmp_inventory_quantity_migration';
    private const STATUS_TABLE = 'tmp_inventory_status_value_migration';

    private object $migration;

    protected function setUp(): void
    {
        parent::setUp();

        $this->migration = require database_path('migrations/2026_10_05_120000_make_inventory_quantities_integers.php');

        Schema::create(self::QUANTITY_TABLE, function (Blueprint $table): void {
            $table->temporary();
            $table->id();
            $table->double('quantity')->default(0);
        });
        Schema::create(self::STATUS_TABLE, function (Blueprint $table): void {
            $table->temporary();
            $table->id();
            $table->string('value');
        });
    }

    protected function tearDown(): void
    {
        DB::statement('DROP TEMPORARY TABLE IF EXISTS ' . self::QUANTITY_TABLE);
        DB::statement('DROP TEMPORARY TABLE IF EXISTS ' . self::STATUS_TABLE);

        parent::tearDown();
    }

    #[Test]
    public function quantities_are_rounded_half_up_instead_of_half_to_even(): void
    {
        // [Bestand, erwartet] – ROUND() direkt auf DOUBLE ergab 0,5 → 0 und 2,5 → 2
        $cases = [[0.5, 1], [1.5, 2], [2.5, 3], [2.49, 2], [7.0, 7], [-1.5, 0]];
        foreach ($cases as $index => [$quantity]) {
            DB::table(self::QUANTITY_TABLE)->insert(['id' => $index + 1, 'quantity' => $quantity]);
        }

        $this->migration->roundQuantities(self::QUANTITY_TABLE);

        $this->assertSame(
            array_column($cases, 1),
            DB::table(self::QUANTITY_TABLE)->orderBy('id')->pluck('quantity')->map(fn ($value): int => (int) $value)->all()
        );
    }

    #[Test]
    public function status_values_are_normalised_to_whole_numbers(): void
    {
        // [Bestand, erwartet]
        $cases = [
            ['0,5', '1'],
            ['2.5', '3'],
            ['2,5', '3'],
            [' 7 ', '7'],
            // deutsche Tausenderpunkte
            ['1.000', '1000'],
            // führende 0: Dezimalzahl, kein Tausenderpunkt
            ['0.500', '1'],
            ['12.500', '12500'],
            ['1.000.000', '1000000'],
            ['1.000,5', '1001'],
            // sonst Punkt als Dezimaltrenner
            ['1.5', '2'],
            ['12.34', '12'],
            ['-3', '0'],
            ['kaputt', '0'],
            ['', '0'],
            ['99999999999', '4294967295'],
        ];
        foreach ($cases as $index => [$value]) {
            DB::table(self::STATUS_TABLE)->insert(['id' => $index + 1, 'value' => $value]);
        }

        $this->migration->normalizeStatusValues(self::STATUS_TABLE);

        $this->assertSame(
            array_column($cases, 1),
            DB::table(self::STATUS_TABLE)->orderBy('id')->pluck('value')->map(fn ($value): string => (string) $value)->all()
        );
    }
}
