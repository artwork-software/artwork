<?php

namespace Tests\Feature\Modules\Inventory;

use Artwork\Modules\Inventory\Models\InventoryArticle;
use Artwork\Modules\Inventory\Services\TypeNumberGenerator;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

final class InventoryNumberSequenceTest extends FeatureTestCase
{
    #[Test]
    public function inventory_numbers_are_not_reused_after_permanent_deletion(): void
    {
        $newest = InventoryArticle::factory()->create();
        $issuedNumber = (int) $newest->inventory_number;
        $this->assertGreaterThan(0, $issuedNumber);

        // Endgültig gelöscht: bisher bekam der nächste Artikel genau diese Nummer wieder.
        $newest->forceDelete();

        $next = InventoryArticle::factory()->create();
        $this->assertSame($issuedNumber + 1, (int) $next->inventory_number);
        $this->assertSame(
            str_pad((string) ($issuedNumber + 2), 5, '0', STR_PAD_LEFT),
            TypeNumberGenerator::generateInventoryNumber()
        );
    }
}
