<?php

namespace Tests\Unit\Modules\MoneySource\Services;

use Artwork\Modules\MoneySource\Models\MoneySource;
use Artwork\Modules\MoneySource\Services\MoneySourceCalculationService;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class MoneySourceCalculationServiceTest extends TestCase
{
    private MoneySourceCalculationService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(MoneySourceCalculationService::class);
    }

    #[Test]
    public function get_position_sum_returns_zero_when_no_linked_entries(): void
    {
        $moneySource = MoneySource::factory()->create(['is_group' => false]);

        $sum = $this->service->getPositionSumOfOneMoneySource($moneySource);

        $this->assertSame(0.0, $sum);
    }

    #[Test]
    public function get_position_sum_returns_zero_for_empty_group(): void
    {
        $group = MoneySource::factory()->create(['is_group' => true]);

        $sum = $this->service->getPositionSumOfOneMoneySource($group);

        $this->assertSame(0.0, $sum);
    }
}
