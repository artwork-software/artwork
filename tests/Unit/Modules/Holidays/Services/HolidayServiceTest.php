<?php

namespace Tests\Unit\Modules\Holidays\Services;

use Artwork\Modules\Holidays\Models\Holiday;
use Artwork\Modules\Holidays\Models\Subdivision;
use Artwork\Modules\Holidays\Services\HolidayService;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class HolidayServiceTest extends TestCase
{
    private HolidayService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(HolidayService::class);
    }

    private function makeSubdivision(string $code = 'BE'): Subdivision
    {
        return Subdivision::create([
            'name' => 'Test Subdivision ' . $code,
            'code' => $code,
            'country_code' => 'DE',
        ]);
    }

    #[Test]
    public function create_persists_holiday_with_subdivision(): void
    {
        $subdivision = $this->makeSubdivision('BE');

        $holiday = $this->service->create(
            name: 'New Year',
            subdivision: $subdivision,
            date: Carbon::parse('2024-01-01'),
            endDate: Carbon::parse('2024-01-01'),
            countryCode: 'DE',
            yearly: true,
            rota: 0,
            remote_identifier: null,
            from_api: false,
            color: '#ff0000',
        );

        $this->assertInstanceOf(Holiday::class, $holiday);
        $this->assertTrue($holiday->exists);
        $this->assertSame('New Year', $holiday->name);
        $this->assertCount(1, $holiday->subdivisions);
        $this->assertSame($subdivision->id, $holiday->subdivisions->first()->id);
    }

    #[Test]
    public function create_persists_holiday_with_subdivision_array(): void
    {
        $subdivision1 = $this->makeSubdivision('BE');
        $subdivision2 = $this->makeSubdivision('BW');

        $holiday = $this->service->create(
            name: 'Multi region',
            subdivision: [$subdivision1->id, $subdivision2->id],
            date: Carbon::parse('2024-05-01'),
            endDate: Carbon::parse('2024-05-01'),
            countryCode: 'DE',
            yearly: true,
            color: '#00ff00',
        );

        $this->assertCount(2, $holiday->subdivisions);
    }

    #[Test]
    public function get_all_imported_returns_only_from_api_holidays(): void
    {
        $subdivision = $this->makeSubdivision('BE');
        $this->service->create(
            name: 'Local Holiday',
            subdivision: $subdivision,
            date: Carbon::parse('2024-01-01'),
            endDate: Carbon::parse('2024-01-01'),
            countryCode: 'DE',
            yearly: false,
            from_api: false,
            color: '#aaaaaa',
        );
        $this->service->create(
            name: 'API Holiday',
            subdivision: $subdivision,
            date: Carbon::parse('2024-02-01'),
            endDate: Carbon::parse('2024-02-01'),
            countryCode: 'DE',
            yearly: false,
            from_api: true,
            color: '#bbbbbb',
        );

        $imported = $this->service->getAllImported();

        $this->assertInstanceOf(Collection::class, $imported);
        $this->assertCount(1, $imported);
        $this->assertSame('API Holiday', $imported->first()->name);
    }

    #[Test]
    public function get_all_returns_paginator(): void
    {
        $subdivision = $this->makeSubdivision('BE');
        for ($i = 0; $i < 3; $i++) {
            $this->service->create(
                name: 'Holiday ' . $i,
                subdivision: $subdivision,
                date: Carbon::parse('2024-01-0' . ($i + 1)),
                endDate: Carbon::parse('2024-01-0' . ($i + 1)),
                countryCode: 'DE',
                yearly: false,
                color: '#ffffff',
            );
        }

        $paginator = $this->service->getAll(15);

        $this->assertInstanceOf(LengthAwarePaginator::class, $paginator);
        $this->assertSame(3, $paginator->total());
    }

    #[Test]
    public function delete_all_from_api_deletes_only_api_holidays(): void
    {
        $subdivision = $this->makeSubdivision('BE');
        $localHoliday = $this->service->create(
            name: 'Local',
            subdivision: $subdivision,
            date: Carbon::parse('2024-01-01'),
            endDate: Carbon::parse('2024-01-01'),
            countryCode: 'DE',
            yearly: false,
            from_api: false,
            color: '#aaaaaa',
        );
        $apiHoliday = $this->service->create(
            name: 'From API',
            subdivision: $subdivision,
            date: Carbon::parse('2024-02-01'),
            endDate: Carbon::parse('2024-02-01'),
            countryCode: 'DE',
            yearly: false,
            from_api: true,
            color: '#bbbbbb',
        );

        $this->service->deleteAllFromApi();

        $this->assertDatabaseHas('holidays', ['id' => $localHoliday->id]);
        $this->assertDatabaseMissing('holidays', ['id' => $apiHoliday->id]);
    }

    #[Test]
    public function merge_holidays_aggregates_by_name_and_dates(): void
    {
        $subdivision = $this->makeSubdivision('BE');
        $selected = collect([['id' => $subdivision->id, 'code' => 'BE']]);

        $response = [
            [
                'country' => 'DE',
                [
                    'id' => 'h-1',
                    'startDate' => '2024-01-01',
                    'endDate' => '2024-01-01',
                    'type' => 'Public',
                    'name' => [['text' => 'New Year']],
                    'regionalScope' => 'National',
                    'temporalScope' => 'FullDay',
                    'nationwide' => true,
                    'subdivisions' => [
                        ['shortName' => 'BE'],
                    ],
                ],
            ],
        ];

        $result = $this->service->mergeHolidays($response, $selected);

        $this->assertIsArray($result);
        $this->assertCount(1, $result);
        $this->assertSame('New Year', $result[0]['name']);
        $this->assertCount(1, $result[0]['subdivisions']);
    }

    #[Test]
    public function merge_holidays_returns_empty_array_when_no_holidays(): void
    {
        $result = $this->service->mergeHolidays([], collect());

        $this->assertSame([], $result);
    }
}
