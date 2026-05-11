<?php

namespace Tests\Unit\Pure\Enums\ServiceProvider;

use Artwork\Modules\ServiceProvider\Enums\ServiceProviderTypes;
use PHPUnit\Framework\Attributes\Test;
use Tests\Unit\UnitTestCase;

final class ServiceProviderTypesTest extends UnitTestCase
{
    #[Test]
    public function it_has_work_case(): void
    {
        $this->assertSame('work', ServiceProviderTypes::WORK->value);
    }

    #[Test]
    public function it_has_housing_case(): void
    {
        $this->assertSame('housing', ServiceProviderTypes::HOUSING->value);
    }

    #[Test]
    public function it_has_two_cases(): void
    {
        $this->assertCount(2, ServiceProviderTypes::cases());
    }

    #[Test]
    public function try_from_returns_null_for_unknown(): void
    {
        $this->assertNull(ServiceProviderTypes::tryFrom('catering'));
    }
}
