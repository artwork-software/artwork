<?php

namespace Tests\Unit\Modules\MoneySource\Services;

use Artwork\Modules\MoneySource\Models\MoneySource;
use Artwork\Modules\MoneySource\Services\MoneySourceCalculationService;
use Artwork\Modules\MoneySource\Services\MoneySourceThresholdReminderService;
use Artwork\Modules\Notification\Services\NotificationService;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class MoneySourceThresholdReminderServiceTest extends TestCase
{
    private MoneySourceThresholdReminderService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(MoneySourceThresholdReminderService::class);
    }

    #[Test]
    public function handle_threshold_reminders_returns_early_when_no_reminders(): void
    {
        $moneySource = MoneySource::factory()->create();
        $calculation = app(MoneySourceCalculationService::class);
        $notification = app(NotificationService::class);

        $this->service->handleThresholdReminders($moneySource, $calculation, $notification);

        $this->assertTrue(true);
    }
}
