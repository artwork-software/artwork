<?php

namespace Tests\Unit\Modules\Event\Services;

use App\Settings\EventSettings;
use Artwork\Modules\Event\Services\EventSettingsService;
use PHPUnit\Framework\Attributes\Test;
use Psr\Log\LoggerInterface;
use Tests\TestCase;

final class EventSettingsServiceTest extends TestCase
{
    #[Test]
    public function get_returns_value_from_settings(): void
    {
        $settings = app(EventSettings::class);
        $settings->enable_status = true;

        $service = new EventSettingsService(app(LoggerInterface::class), $settings);

        $this->assertTrue($service->get('enable_status'));
    }

    #[Test]
    public function get_returns_default_when_setting_missing(): void
    {
        $settings = app(EventSettings::class);

        $logger = \Mockery::mock(LoggerInterface::class);
        $logger->shouldReceive('error')->once();

        $service = new EventSettingsService($logger, $settings);

        $this->assertSame('fallback', $service->get('nonexistent_setting', 'fallback'));
    }

    #[Test]
    public function get_returns_null_default_when_setting_missing(): void
    {
        $settings = app(EventSettings::class);

        $logger = \Mockery::mock(LoggerInterface::class);
        $logger->shouldReceive('error')->once();

        $service = new EventSettingsService($logger, $settings);

        $this->assertNull($service->get('nonexistent_setting'));
    }
}
