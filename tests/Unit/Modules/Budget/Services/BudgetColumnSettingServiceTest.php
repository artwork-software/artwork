<?php

namespace Tests\Unit\Modules\Budget\Services;

use Artwork\Modules\Budget\Models\BudgetColumnSetting;
use Artwork\Modules\Budget\Services\BudgetColumnSettingService;
use Illuminate\Database\Eloquent\Collection;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class BudgetColumnSettingServiceTest extends TestCase
{
    private BudgetColumnSettingService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(BudgetColumnSettingService::class);
    }

    #[Test]
    public function get_all_returns_collection(): void
    {
        BudgetColumnSetting::factory()->count(3)->create();

        $result = $this->service->getAll();

        $this->assertInstanceOf(Collection::class, $result);
        $this->assertGreaterThanOrEqual(3, $result->count());
    }

    #[Test]
    public function get_column_name_by_column_position_returns_name(): void
    {
        BudgetColumnSetting::factory()->create([
            'column_position' => 42,
            'column_name' => 'Custom Column',
        ]);

        $result = $this->service->getColumnNameByColumnPosition(42);

        $this->assertSame('Custom Column', $result);
    }

    #[Test]
    public function get_column_name_by_column_position_returns_empty_string_when_missing(): void
    {
        $result = $this->service->getColumnNameByColumnPosition(99999);

        $this->assertSame('', $result);
    }
}
