<?php

namespace Tests\Unit\Modules\Inventory\Services;

use Artwork\Modules\Inventory\Services\InventoryArticleFilterResolver;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class InventoryArticleFilterResolverTest extends TestCase
{
    private InventoryArticleFilterResolver $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(InventoryArticleFilterResolver::class);
    }

    #[Test]
    public function resolve_returns_empty_source_when_no_user_or_request(): void
    {
        $result = $this->service->resolve(null, null);

        $this->assertSame('empty', $result['source']);
        $this->assertSame([], $result['filters']);
        $this->assertSame([], $result['tag_ids']);
        $this->assertNull($result['filter_preset_id']);
    }
}
