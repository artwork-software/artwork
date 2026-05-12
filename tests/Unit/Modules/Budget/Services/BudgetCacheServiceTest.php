<?php

namespace Tests\Unit\Modules\Budget\Services;

use Artwork\Modules\Budget\Services\BudgetCacheService;
use Illuminate\Support\Facades\Cache;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class BudgetCacheServiceTest extends TestCase
{
    private BudgetCacheService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(BudgetCacheService::class);
    }

    #[Test]
    public function get_budget_payload_returns_null_when_no_cache(): void
    {
        Cache::flush();

        $this->assertNull($this->service->getBudgetPayload(42));
    }

    #[Test]
    public function set_budget_payload_writes_to_cache(): void
    {
        $payload = ['table' => 'value'];

        $this->service->setBudgetPayload(99, $payload);

        $this->assertSame($payload, $this->service->getBudgetPayload(99));
    }

    #[Test]
    public function forget_budget_payload_removes_cache_entry(): void
    {
        $this->service->setBudgetPayload(5, ['x' => 1]);
        $this->assertNotNull($this->service->getBudgetPayload(5));

        $this->service->forgetBudgetPayload(5);

        $this->assertNull($this->service->getBudgetPayload(5));
    }

    #[Test]
    public function get_static_lookups_returns_expected_keys(): void
    {
        Cache::flush();

        $result = $this->service->getStaticLookups();

        $this->assertArrayHasKey('moneySources', $result);
        $this->assertArrayHasKey('contractTypes', $result);
        $this->assertArrayHasKey('companyTypes', $result);
        $this->assertArrayHasKey('currencies', $result);
    }

    #[Test]
    public function forget_static_lookups_clears_cache(): void
    {
        $this->service->getStaticLookups();
        $this->service->forgetStaticLookups();

        // No exception thrown is enough.
        $this->assertNull(Cache::get('budget:static:money_sources'));
        $this->assertNull(Cache::get('budget:static:contract_types'));
        $this->assertNull(Cache::get('budget:static:company_types'));
        $this->assertNull(Cache::get('budget:static:currencies'));
    }
}
