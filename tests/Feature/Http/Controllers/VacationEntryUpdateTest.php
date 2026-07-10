<?php

namespace Tests\Feature\Http\Controllers;

use Artwork\Modules\Availability\Models\Availability;
use Artwork\Modules\Availability\Models\AvailabilitySeries;
use Artwork\Modules\User\Models\User;
use Artwork\Modules\Vacation\Models\Vacation;
use Artwork\Modules\Vacation\Models\VacationSeries;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

final class VacationEntryUpdateTest extends FeatureTestCase
{
    #[Test]
    public function guest_cannot_update_entry(): void
    {
        $vacation = Vacation::factory()->create();

        $this->patch(route('update.vacation.entry', $vacation), [])
            ->assertRedirect(route('login'));
    }

    #[Test]
    public function user_without_permission_cannot_update_foreign_entry(): void
    {
        $vacation = Vacation::factory()->create();
        $otherUser = User::factory()->create();

        $this->actingAs($otherUser)
            ->patch(route('update.vacation.entry', $vacation), [
                'date' => '2026-08-03',
                'type' => 'vacation',
            ])
            ->assertForbidden();
    }

    #[Test]
    public function user_can_convert_own_single_vacation_to_period(): void
    {
        $user = User::factory()->create();
        $vacation = Vacation::factory()->create([
            'vacationer_type' => User::class,
            'vacationer_id' => $user->id,
            'date' => '2026-08-03',
            'full_day' => true,
            'is_series' => false,
            'series_id' => null,
        ]);

        $this->actingAs($user)
            ->patch(route('update.vacation.entry', $vacation), [
                'date' => '2026-08-03',
                'type' => 'vacation',
                'full_day' => true,
                'is_series' => true,
                'series_repeat' => 'daily',
                'series_repeat_until' => '2026-08-05',
            ])
            ->assertRedirect();

        $this->assertDatabaseMissing('vacations', ['id' => $vacation->id]);
        $this->assertSame(
            3,
            $user->vacations()->whereBetween('date', ['2026-08-03', '2026-08-05'])->count()
        );
    }

    #[Test]
    public function updating_a_series_entry_replaces_the_whole_series(): void
    {
        $user = User::factory()->create();
        $series = VacationSeries::factory()->create(['frequency' => 'daily', 'end_date' => '2026-08-05']);
        $seriesVacations = collect(['2026-08-03', '2026-08-04', '2026-08-05'])->map(
            fn(string $date) => Vacation::factory()->create([
                'vacationer_type' => User::class,
                'vacationer_id' => $user->id,
                'date' => $date,
                'full_day' => true,
                'is_series' => true,
                'series_id' => $series->id,
            ])
        );

        $this->actingAs($user)
            ->patch(route('update.vacation.entry', $seriesVacations->first()), [
                'date' => '2026-08-10',
                'type' => 'vacation',
                'full_day' => true,
                'is_series' => true,
                'series_repeat' => 'daily',
                'series_repeat_until' => '2026-08-11',
            ])
            ->assertRedirect();

        $this->assertDatabaseMissing('vacation_series', ['id' => $series->id]);
        foreach ($seriesVacations as $seriesVacation) {
            $this->assertDatabaseMissing('vacations', ['id' => $seriesVacation->id]);
        }
        $this->assertSame(
            2,
            $user->vacations()->whereBetween('date', ['2026-08-10', '2026-08-11'])->count()
        );
    }

    #[Test]
    public function updating_entry_can_switch_type_from_vacation_to_availability(): void
    {
        $user = User::factory()->create();
        $vacation = Vacation::factory()->create([
            'vacationer_type' => User::class,
            'vacationer_id' => $user->id,
            'date' => '2026-08-03',
            'full_day' => true,
            'is_series' => false,
            'series_id' => null,
        ]);

        $this->actingAs($user)
            ->patch(route('update.vacation.entry', $vacation), [
                'date' => '2026-08-03',
                'type' => 'available',
                'full_day' => true,
                'is_series' => false,
            ])
            ->assertRedirect();

        $this->assertDatabaseMissing('vacations', ['id' => $vacation->id]);
        $this->assertSame(1, $user->availabilities()->whereDate('date', '2026-08-03')->count());
    }

    #[Test]
    public function user_can_update_own_availability_entry(): void
    {
        $user = User::factory()->create();
        $availability = Availability::factory()->create([
            'available_type' => User::class,
            'available_id' => $user->id,
            'date' => '2026-08-03',
            'full_day' => true,
            'is_series' => false,
            'series_id' => null,
        ]);

        $this->actingAs($user)
            ->patch(route('update.availability.entry', $availability), [
                'date' => '2026-08-04',
                'type' => 'available',
                'full_day' => false,
                'start_time' => '10:00',
                'end_time' => '14:00',
                'is_series' => false,
            ])
            ->assertRedirect();

        $this->assertDatabaseMissing('availabilities', ['id' => $availability->id]);
        $this->assertSame(1, $user->availabilities()->whereDate('date', '2026-08-04')->count());
    }

    #[Test]
    public function user_without_permission_cannot_update_foreign_availability_entry(): void
    {
        $availability = Availability::factory()->create([
            'available_type' => User::class,
            'available_id' => User::factory()->create()->id,
            'series_id' => null,
        ]);
        $otherUser = User::factory()->create();

        $this->actingAs($otherUser)
            ->patch(route('update.availability.entry', $availability), [
                'date' => '2026-08-03',
                'type' => 'available',
            ])
            ->assertForbidden();
    }

    #[Test]
    public function user_without_permission_cannot_delete_foreign_vacation_series(): void
    {
        $user = User::factory()->create();
        $series = VacationSeries::factory()->create(['frequency' => 'daily', 'end_date' => '2026-08-05']);
        Vacation::factory()->create([
            'vacationer_type' => User::class,
            'vacationer_id' => $user->id,
            'date' => '2026-08-03',
            'is_series' => true,
            'series_id' => $series->id,
        ]);
        $otherUser = User::factory()->create();

        $this->actingAs($otherUser)
            ->delete(route('delete.vacation.series', $series))
            ->assertForbidden();
    }

    #[Test]
    public function user_can_delete_own_availability_series(): void
    {
        $user = User::factory()->create();
        $series = AvailabilitySeries::factory()->create(['frequency' => 'weekly', 'end_date' => '2026-09-01']);
        Availability::factory()->create([
            'available_type' => User::class,
            'available_id' => $user->id,
            'date' => '2026-08-03',
            'is_series' => true,
            'series_id' => $series->id,
        ]);

        $this->actingAs($user)
            ->delete(route('delete.availability.series', $series))
            ->assertRedirect();

        $this->assertDatabaseMissing('availability_series', ['id' => $series->id]);
    }
}
