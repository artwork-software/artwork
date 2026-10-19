<?php

namespace Tests\Feature\Modules\IndividualTimes;

use Artwork\Modules\IndividualTimes\Models\IndividualTime;
use Artwork\Modules\Permission\Enums\PermissionEnum;
use Artwork\Modules\User\Models\User;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

/**
 * Eigene individuelle Zeiten im eigenen Einsatzplan: Recht "can enter own individual times" ab heute,
 * Verwaltungsrechte ("can manage availability"/"can plan shifts") an jedem Tag, ohne Recht gar nicht.
 */
final class OwnIndividualTimeAccessTest extends FeatureTestCase
{
    #[Test]
    public function permission_allows_entering_own_time_from_today_on(): void
    {
        $user = $this->actingAsUserWith(PermissionEnum::CAN_ENTER_OWN_INDIVIDUAL_TIMES->value);

        $this->storeOwnTime($user, now()->toDateString())->assertOk();
        $this->storeOwnTime($user, now()->addDays(3)->toDateString())->assertOk();

        $this->assertSame(2, $user->individualTimes()->count());
    }

    #[Test]
    public function permission_does_not_allow_past_days(): void
    {
        $user = $this->actingAsUserWith(PermissionEnum::CAN_ENTER_OWN_INDIVIDUAL_TIMES->value);

        $this->storeOwnTime($user, now()->subDay()->toDateString())->assertForbidden();

        $this->assertSame(0, $user->individualTimes()->count());
    }

    #[Test]
    public function without_permission_own_times_are_forbidden(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $this->storeOwnTime($user, now()->addDay()->toDateString())->assertForbidden();
    }

    #[Test]
    public function planners_may_enter_own_times_on_past_days(): void
    {
        $planner = $this->actingAsUserWith(PermissionEnum::SHIFT_PLANNER->value);

        $this->storeOwnTime($planner, now()->subWeek()->toDateString())->assertOk();
    }

    #[Test]
    public function permission_does_not_allow_times_of_other_people(): void
    {
        $this->actingAsUserWith(PermissionEnum::CAN_ENTER_OWN_INDIVIDUAL_TIMES->value);
        $other = User::factory()->create();

        $this->storeOwnTime($other, now()->addDay()->toDateString())->assertForbidden();
    }

    #[Test]
    public function moving_a_past_entry_into_the_future_is_forbidden(): void
    {
        $user = $this->actingAsUserWith(PermissionEnum::CAN_ENTER_OWN_INDIVIDUAL_TIMES->value);
        $past = $this->ownTime($user, now()->subDays(2)->toDateString());

        $this->post(route('add.update.individualTimesAndShiftPlanComment'), [
            'modelType' => 0,
            'modelId' => $user->id,
            'individualTimes' => [[
                'id' => $past->id,
                'title' => 'Moved',
                'start_time' => '09:00',
                'end_time' => '10:00',
                'start_date' => now()->addDay()->toDateString(),
            ]],
        ])->assertForbidden();
    }

    #[Test]
    public function own_entries_can_be_edited_and_deleted_from_today_on_only(): void
    {
        $user = $this->actingAsUserWith(PermissionEnum::CAN_ENTER_OWN_INDIVIDUAL_TIMES->value);
        $future = $this->ownTime($user, now()->addDay()->toDateString());
        $past = $this->ownTime($user, now()->subDay()->toDateString());

        $this->patch(route('individual-times.update-single', $future), ['title' => 'Changed'])
            ->assertRedirect();
        $this->assertSame('Changed', $future->fresh()->title);

        $this->patch(route('individual-times.update-single', $past), ['title' => 'Changed'])
            ->assertForbidden();
        $this->delete(route('delete.individualTimes', $past))->assertForbidden();

        $this->delete(route('delete.individualTimes', $future))->assertOk();
        $this->assertModelMissing($future);
    }

    #[Test]
    public function own_series_respects_the_start_date(): void
    {
        $user = $this->actingAsUserWith(PermissionEnum::CAN_ENTER_OWN_INDIVIDUAL_TIMES->value);

        $this->post(route('individual-time-series.store'), $this->seriesPayload($user, now()->subDay()))
            ->assertForbidden();

        $this->post(route('individual-time-series.store'), $this->seriesPayload($user, now()))
            ->assertRedirect();
        $this->assertGreaterThan(0, $user->individualTimes()->whereNotNull('series_uuid')->count());
    }

    #[Test]
    public function series_cannot_span_more_than_two_years(): void
    {
        $user = $this->actingAsUserWith(PermissionEnum::CAN_ENTER_OWN_INDIVIDUAL_TIMES->value);
        $payload = $this->seriesPayload($user, now());
        $payload['end_date'] = now()->addYears(50)->toDateString();

        $this->post(route('individual-time-series.store'), $payload)->assertSessionHasErrors('end_date');

        $this->assertSame(0, $user->individualTimes()->count());
    }

    #[Test]
    public function own_time_fields_are_validated(): void
    {
        $user = $this->actingAsUserWith(PermissionEnum::CAN_ENTER_OWN_INDIVIDUAL_TIMES->value);
        $base = [
            'modelType' => 0,
            'modelId' => $user->id,
        ];
        $time = [
            'title' => 'Probe',
            'start_time' => '09:00',
            'end_time' => '12:00',
            'start_date' => now()->toDateString(),
            'break_minutes' => 0,
        ];

        $this->postJson(route('add.update.individualTimesAndShiftPlanComment'), [
            ...$base,
            'individualTimes' => [[...$time, 'break_minutes' => -100000]],
        ])->assertUnprocessable()->assertJsonValidationErrors('individualTimes.0.break_minutes');
        $this->postJson(route('add.update.individualTimesAndShiftPlanComment'), [
            ...$base,
            'individualTimes' => [[...$time, 'start_time' => 'abc']],
        ])->assertUnprocessable()->assertJsonValidationErrors('individualTimes.0.start_time');
        $this->assertSame(0, $user->individualTimes()->count());

        // gespeicherte Zeiten kommen beim Bearbeiten mit Sekunden zurück
        $this->postJson(route('add.update.individualTimesAndShiftPlanComment'), [
            ...$base,
            'individualTimes' => [[...$time, 'start_time' => '09:00:00', 'end_time' => '12:00:00']],
        ])->assertOk();
    }

    private function storeOwnTime(User $user, string $date): \Illuminate\Testing\TestResponse
    {
        return $this->post(route('add.update.individualTimesAndShiftPlanComment'), [
            'modelType' => 0,
            'modelId' => $user->id,
            'individualTimes' => [[
                'title' => 'Probe',
                'start_time' => '09:00',
                'end_time' => '12:00',
                'start_date' => $date,
                'break_minutes' => 0,
            ]],
        ]);
    }

    private function ownTime(User $user, string $date): IndividualTime
    {
        return IndividualTime::query()->forceCreate([
            'timeable_type' => User::class,
            'timeable_id' => $user->id,
            'title' => 'Own',
            'start_time' => '08:00',
            'end_time' => '09:00',
            'start_date' => $date,
            'end_date' => $date,
            'break_minutes' => 0,
            'working_time_minutes' => 60,
            'full_day' => false,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function seriesPayload(User $user, \Illuminate\Support\Carbon $start): array
    {
        return [
            'title' => 'Training',
            'start_date' => $start->toDateString(),
            'end_date' => $start->copy()->addWeeks(2)->toDateString(),
            'start_time' => '09:00',
            'end_time' => '10:00',
            'full_day' => false,
            'break_minutes' => 0,
            'frequency' => 'weekly',
            'interval' => 1,
            'weekdays' => [1, 2, 3, 4, 5, 6, 7],
            'subjects' => [['type' => 'user', 'id' => $user->id]],
        ];
    }
}
