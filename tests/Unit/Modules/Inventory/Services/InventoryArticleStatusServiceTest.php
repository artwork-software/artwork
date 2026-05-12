<?php

namespace Tests\Unit\Modules\Inventory\Services;

use Artwork\Modules\Inventory\Services\InventoryArticleStatusService;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class InventoryArticleStatusServiceTest extends TestCase
{
    #[Test]
    public function service_can_be_resolved_from_container(): void
    {
        $service = app(InventoryArticleStatusService::class);

        $this->assertInstanceOf(InventoryArticleStatusService::class, $service);
    }
}
