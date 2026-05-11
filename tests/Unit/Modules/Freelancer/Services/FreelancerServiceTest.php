<?php

namespace Tests\Unit\Modules\Freelancer\Services;

use Artwork\Modules\Availability\Models\Availability;
use Artwork\Modules\Freelancer\Models\Freelancer;
use Artwork\Modules\Freelancer\Services\FreelancerService;
use Artwork\Modules\Vacation\Models\Vacation;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as SupportCollection;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class FreelancerServiceTest extends TestCase
{
    private FreelancerService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(FreelancerService::class);
    }

    #[Test]
    public function get_vacations_by_date_returns_only_matching_vacations(): void
    {
        $freelancer = Freelancer::factory()->create();
        Vacation::create([
            'vacationer_type' => Freelancer::class,
            'vacationer_id' => $freelancer->id,
            'date' => '2024-05-10',
            'full_day' => true,
            'is_series' => false,
            'type' => 'OFF_WORK',
        ]);
        Vacation::create([
            'vacationer_type' => Freelancer::class,
            'vacationer_id' => $freelancer->id,
            'date' => '2024-05-11',
            'full_day' => true,
            'is_series' => false,
            'type' => 'OFF_WORK',
        ]);

        $result = $this->service->getVacationsByDateOrderedByDateAscending(
            $freelancer,
            Carbon::parse('2024-05-10')
        );

        $this->assertInstanceOf(Collection::class, $result);
        $this->assertCount(1, $result);
        $this->assertSame('2024-05-10', (string) $result->first()->date);
    }

    #[Test]
    public function get_vacations_by_month_returns_vacations_in_month(): void
    {
        $freelancer = Freelancer::factory()->create();
        Vacation::create([
            'vacationer_type' => Freelancer::class,
            'vacationer_id' => $freelancer->id,
            'date' => '2024-05-10',
            'full_day' => true,
            'is_series' => false,
            'type' => 'OFF_WORK',
        ]);
        Vacation::create([
            'vacationer_type' => Freelancer::class,
            'vacationer_id' => $freelancer->id,
            'date' => '2024-06-01',
            'full_day' => true,
            'is_series' => false,
            'type' => 'OFF_WORK',
        ]);

        $result = $this->service->getVacationsByMonthOrderedByDateAscending(
            $freelancer,
            Carbon::parse('2024-05-15')
        );

        $this->assertCount(1, $result);
    }

    #[Test]
    public function get_availabilities_by_date_returns_only_matching(): void
    {
        $freelancer = Freelancer::factory()->create();
        Availability::create([
            'start_time' => '09:00',
            'end_time' => '17:00',
            'date' => '2024-05-10',
            'full_day' => false,
            'is_series' => false,
            'available_type' => Freelancer::class,
            'available_id' => $freelancer->id,
        ]);
        Availability::create([
            'start_time' => '09:00',
            'end_time' => '17:00',
            'date' => '2024-05-11',
            'full_day' => false,
            'is_series' => false,
            'available_type' => Freelancer::class,
            'available_id' => $freelancer->id,
        ]);

        $result = $this->service->getAvailabilitiesByDateOrderedByDateAscending(
            $freelancer,
            Carbon::parse('2024-05-10')
        );

        $this->assertCount(1, $result);
    }

    #[Test]
    public function get_availabilities_by_month_returns_those_in_month(): void
    {
        $freelancer = Freelancer::factory()->create();
        Availability::create([
            'start_time' => '09:00',
            'end_time' => '17:00',
            'date' => '2024-05-10',
            'full_day' => false,
            'is_series' => false,
            'available_type' => Freelancer::class,
            'available_id' => $freelancer->id,
        ]);
        Availability::create([
            'start_time' => '09:00',
            'end_time' => '17:00',
            'date' => '2024-07-10',
            'full_day' => false,
            'is_series' => false,
            'available_type' => Freelancer::class,
            'available_id' => $freelancer->id,
        ]);

        $result = $this->service->getAvailabilitiesByMonthOrderedByDateAscending(
            $freelancer,
            Carbon::parse('2024-05-01')
        );

        $this->assertCount(1, $result);
    }

    #[Test]
    public function get_shifts_with_events_returns_collection(): void
    {
        $freelancer = Freelancer::factory()->create();

        $shifts = $this->service->getShiftsWithEventsOrderedByStart($freelancer);

        $this->assertInstanceOf(Collection::class, $shifts);
        $this->assertCount(0, $shifts);
    }

    #[Test]
    public function search_freelancers_returns_support_collection(): void
    {
        Freelancer::factory()->create(['first_name' => 'Alice']);

        $result = $this->service->searchFreelancers('Alice');

        $this->assertInstanceOf(SupportCollection::class, $result);
    }
}
