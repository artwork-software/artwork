<?php

namespace Tests\Unit\Modules\Freelancer\Services;

use Artwork\Modules\Freelancer\Services\FreelancerVacationService;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class FreelancerVacationServiceTest extends TestCase
{
    private FreelancerVacationService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(FreelancerVacationService::class);
    }

    #[Test]
    public function service_can_be_resolved_from_container(): void
    {
        $this->assertInstanceOf(FreelancerVacationService::class, $this->service);
    }
}
