<?php

namespace Tests\Unit\Modules\Holidays\Services;

use Artwork\Modules\Holidays\Frontend\ShowDto;
use Artwork\Modules\Holidays\Models\Holiday;
use Artwork\Modules\Holidays\Services\HolidayFrontendService;
use Carbon\Carbon;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class HolidayFrontendServiceTest extends TestCase
{
    private HolidayFrontendService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(HolidayFrontendService::class);
    }

    #[Test]
    public function create_show_dto_returns_show_dto_for_holiday(): void
    {
        $holiday = Holiday::create([
            'name' => 'Christmas',
            'date' => Carbon::parse('2024-12-24'),
            'end_date' => Carbon::parse('2024-12-24'),
            'rota' => 0,
            'country' => 'DE',
            'from_api' => false,
            'yearly' => true,
        ]);

        $dto = $this->service->createShowDto($holiday);

        $this->assertInstanceOf(ShowDto::class, $dto);
        $this->assertSame($holiday->id, $dto->holiday->id);

        $array = $dto->toArray();
        $this->assertSame($holiday->id, $array['id']);
        $this->assertSame('Christmas', $array['name']);
        $this->assertArrayHasKey('date', $array);
        $this->assertArrayHasKey('end_date', $array);
        $this->assertArrayHasKey('subdivisions', $array);
        $this->assertSame(0, $array['rota']);
        $this->assertFalse($array['from_api']);
    }
}
