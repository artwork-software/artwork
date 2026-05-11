<?php

namespace Tests\Unit\Modules\CompanyType\Services;

use Artwork\Modules\CompanyType\Models\CompanyType;
use Artwork\Modules\CompanyType\Services\CompanyTypeService;
use Illuminate\Database\Eloquent\Collection;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class CompanyTypeServiceTest extends TestCase
{
    private CompanyTypeService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(CompanyTypeService::class);
    }

    #[Test]
    public function get_all_returns_empty_collection(): void
    {
        $result = $this->service->getAll();

        $this->assertInstanceOf(Collection::class, $result);
        $this->assertCount(0, $result);
    }

    #[Test]
    public function get_all_returns_all_company_types(): void
    {
        CompanyType::factory()->count(3)->create();

        $result = $this->service->getAll();

        $this->assertCount(3, $result);
        $this->assertContainsOnlyInstancesOf(CompanyType::class, $result);
    }
}
