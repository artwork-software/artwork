<?php

namespace Tests\Unit\Modules\Budget\Services;

use Artwork\Modules\Budget\Enums\BudgetTypeEnum;
use Artwork\Modules\Budget\Models\Table;
use Artwork\Modules\Budget\Services\MainPositionService;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class MainPositionServiceTest extends TestCase
{
    private MainPositionService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(MainPositionService::class);
    }

    #[Test]
    public function create_main_position_persists_record(): void
    {
        $table = Table::factory()->create();

        $mainPosition = $this->service->createMainPosition(
            $table,
            BudgetTypeEnum::BUDGET_TYPE_COST,
            'Hauptposition',
            0,
        );

        $this->assertNotNull($mainPosition->id);
        $this->assertSame('BUDGET_TYPE_COST', $mainPosition->type);
        $this->assertSame('Hauptposition', $mainPosition->name);
        $this->assertSame($table->id, $mainPosition->table_id);
    }

    #[Test]
    public function create_main_position_supports_earning_type(): void
    {
        $table = Table::factory()->create();

        $mainPosition = $this->service->createMainPosition(
            $table,
            BudgetTypeEnum::BUDGET_TYPE_EARNING,
            'Erträge',
            1,
        );

        $this->assertSame('BUDGET_TYPE_EARNING', $mainPosition->type);
        $this->assertSame(1, $mainPosition->position);
    }
}
