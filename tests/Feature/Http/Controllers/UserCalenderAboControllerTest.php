<?php

namespace Tests\Feature\Http\Controllers;

use Artwork\Modules\User\Models\User;
use Artwork\Modules\User\Models\UserCalendarAbo;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

final class UserCalenderAboControllerTest extends FeatureTestCase
{
    #[Test]
    public function updateCannotReassignTheSubscriptionToAnotherUser(): void
    {
        $victim = User::factory()->create();
        $attacker = User::factory()->create();
        $calendarAbo = $this->createCalendarAbo($attacker);

        $this->actingAs($attacker)
            ->patch(
                route('user.calendar.abo.update', $calendarAbo->id),
                ['user_id' => $victim->id]
            );

        $this->assertSame($attacker->id, $calendarAbo->fresh()->user_id);
    }

    #[Test]
    public function updateOfAForeignSubscriptionIsForbidden(): void
    {
        $victim = User::factory()->create();
        $attacker = User::factory()->create();
        $calendarAbo = $this->createCalendarAbo($victim, ['specific_rooms' => false]);

        $response = $this->actingAs($attacker)
            ->patch(
                route('user.calendar.abo.update', $calendarAbo->id),
                ['specific_rooms' => true]
            );

        $response->assertForbidden();
        $this->assertFalse((bool) $calendarAbo->fresh()->specific_rooms);
        $this->assertSame($victim->id, $calendarAbo->fresh()->user_id);
    }

    #[Test]
    public function updateCannotOverwriteTheFeedUuid(): void
    {
        $owner = User::factory()->create();
        $calendarAbo = $this->createCalendarAbo($owner);
        $originalUuid = $calendarAbo->calendar_abo_id;

        $this->actingAs($owner)
            ->patch(
                route('user.calendar.abo.update', $calendarAbo->id),
                ['calendar_abo_id' => 'attacker-chosen-uuid']
            );

        $this->assertSame($originalUuid, $calendarAbo->fresh()->calendar_abo_id);
        $this->get(route('user-calendar-abo.show', 'attacker-chosen-uuid'))->assertNotFound();
    }

    #[Test]
    public function storeAlwaysGeneratesTheFeedUuidOnTheServer(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('user.calendar.abo.create'), [
            'calendar_abo_id' => 'attacker-chosen-uuid',
            'user_id' => User::factory()->create()->id,
            'date_range' => false,
            'specific_event_types' => false,
            'event_types' => [],
            'specific_rooms' => false,
            'selected_rooms' => [],
            'specific_areas' => false,
            'selected_areas' => [],
            'enable_notification' => false,
            'notification_time' => 15,
            'notification_time_unit' => 'minutes',
        ]);

        $calendarAbo = UserCalendarAbo::query()->where('user_id', $user->id)->sole();

        $this->assertNotSame('attacker-chosen-uuid', $calendarAbo->calendar_abo_id);
        $this->assertTrue(Str::isUuid($calendarAbo->calendar_abo_id));
    }

    #[Test]
    public function theOwnerCanStillUpdateTheirOwnSubscription(): void
    {
        $owner = User::factory()->create();
        $calendarAbo = $this->createCalendarAbo($owner);

        $this->actingAs($owner)
            ->patch(route('user.calendar.abo.update', $calendarAbo->id), [
                'id' => $calendarAbo->id,
                'date_range' => true,
                'start_date' => '2026-07-01',
                'end_date' => '2026-07-31',
                'specific_event_types' => false,
                'event_types' => [],
                'specific_rooms' => false,
                'selected_rooms' => [],
                'specific_areas' => false,
                'selected_areas' => [],
                'enable_notification' => true,
                'notification_time' => 45,
                'notification_time_unit' => 'minutes',
            ]);

        $calendarAbo = $calendarAbo->fresh();

        $this->assertTrue((bool) $calendarAbo->date_range);
        $this->assertTrue((bool) $calendarAbo->enable_notification);
        $this->assertSame(45, $calendarAbo->notification_time);
        $this->assertSame($owner->id, $calendarAbo->user_id);
    }

    /**
     * @param array<string, mixed> $attributes
     */
    private function createCalendarAbo(User $user, array $attributes = []): UserCalendarAbo
    {
        return UserCalendarAbo::query()->forceCreate(array_merge([
            'user_id' => $user->id,
            'calendar_abo_id' => fake()->uuid(),
            'date_range' => false,
            'specific_event_types' => false,
            'event_types' => [],
            'specific_rooms' => false,
            'selected_rooms' => [],
            'specific_areas' => false,
            'selected_areas' => [],
            'enable_notification' => false,
        ], $attributes));
    }
}
