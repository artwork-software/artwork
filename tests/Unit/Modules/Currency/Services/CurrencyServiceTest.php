<?php

namespace Tests\Unit\Modules\Currency\Services;

use Artwork\Modules\Currency\Models\Currency;
use Artwork\Modules\Currency\Services\CurrencyService;
use Illuminate\Database\Eloquent\Collection;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class CurrencyServiceTest extends TestCase
{
    private CurrencyService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(CurrencyService::class);
    }

    #[Test]
    public function get_all_returns_empty_collection_when_no_currencies_exist(): void
    {
        $result = $this->service->getAll();

        $this->assertInstanceOf(Collection::class, $result);
        $this->assertCount(0, $result);
    }

    #[Test]
    public function get_all_returns_all_currencies(): void
    {
        Currency::factory()->count(3)->create();

        $result = $this->service->getAll();

        $this->assertCount(3, $result);
        $this->assertContainsOnlyInstancesOf(Currency::class, $result);
    }

    #[Test]
    public function get_all_excludes_soft_deleted_currencies(): void
    {
        Currency::factory()->count(2)->create();
        $deleted = Currency::factory()->create();
        $deleted->delete();

        $result = $this->service->getAll();

        $this->assertCount(2, $result);
        $this->assertFalse($result->contains('id', $deleted->id));
    }
}
