<?php

namespace Tests\Unit\Modules\Contract\Services;

use Artwork\Modules\Contract\Models\ContractType;
use Artwork\Modules\Contract\Services\ContractTypeService;
use Illuminate\Database\Eloquent\Collection;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class ContractTypeServiceTest extends TestCase
{
    private ContractTypeService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(ContractTypeService::class);
    }

    #[Test]
    public function get_all_returns_empty_collection_when_no_contract_types_exist(): void
    {
        $result = $this->service->getAll();

        $this->assertInstanceOf(Collection::class, $result);
        $this->assertCount(0, $result);
    }

    #[Test]
    public function get_all_returns_all_contract_types(): void
    {
        ContractType::factory()->count(3)->create();

        $result = $this->service->getAll();

        $this->assertCount(3, $result);
        $this->assertContainsOnlyInstancesOf(ContractType::class, $result);
    }
}
