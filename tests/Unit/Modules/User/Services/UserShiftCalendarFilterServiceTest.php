<?php

namespace Tests\Unit\Modules\User\Services;

use Artwork\Modules\User\Services\UserShiftCalendarFilterService;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class UserShiftCalendarFilterServiceTest extends TestCase
{
    #[Test]
    public function service_can_be_resolved_from_container(): void
    {
        $service = app(UserShiftCalendarFilterService::class);

        $this->assertInstanceOf(UserShiftCalendarFilterService::class, $service);
    }
}
