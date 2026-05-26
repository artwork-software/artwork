<?php

namespace Tests\Unit\Modules\User\Services;

use Artwork\Modules\User\Models\UserCalendarFilter;
use Artwork\Modules\User\Services\UserCalendarFilterService;
use Illuminate\Database\Eloquent\Collection;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class UserCalendarFilterServiceTest extends TestCase
{
    private UserCalendarFilterService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(UserCalendarFilterService::class);
    }

    #[Test]
    public function get_all_returns_collection(): void
    {
        UserCalendarFilter::factory()->count(2)->create();

        $result = $this->service->getAll();

        $this->assertInstanceOf(Collection::class, $result);
        $this->assertCount(2, $result);
    }

    #[Test]
    public function update_or_fail_persists_attributes(): void
    {
        $filter = UserCalendarFilter::factory()->create();

        $updated = $this->service->updateOrFail($filter, [
            'event_properties' => [1, 2, 3],
        ]);

        $this->assertSame($filter->id, $updated->id);
        $this->assertDatabaseHas('user_calendar_filters', [
            'id' => $filter->id,
        ]);
        $this->assertSame([1, 2, 3], $filter->fresh()->event_properties);
    }

    #[Test]
    public function update_event_properties_of_all_applies_to_each_filter(): void
    {
        UserCalendarFilter::factory()->count(3)->create();

        $this->service->updateEventPropertiesOfAll([10, 20]);

        foreach (UserCalendarFilter::all() as $filter) {
            $this->assertSame([10, 20], $filter->event_properties);
        }
    }
}
