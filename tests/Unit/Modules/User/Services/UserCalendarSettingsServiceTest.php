<?php

namespace Tests\Unit\Modules\User\Services;

use Artwork\Modules\User\Services\UserCalendarSettingsService;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class UserCalendarSettingsServiceTest extends TestCase
{
    #[Test]
    public function service_can_be_resolved_from_container(): void
    {
        $service = app(UserCalendarSettingsService::class);

        $this->assertInstanceOf(UserCalendarSettingsService::class, $service);
    }
}
