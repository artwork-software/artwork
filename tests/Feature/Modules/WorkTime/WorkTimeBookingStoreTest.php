<?php

namespace Tests\Feature\Modules\WorkTime;

use Artwork\Modules\User\Models\User;
use Artwork\Modules\User\Models\UserContractAssign;
use Artwork\Modules\WorkTime\Models\UserOvertime;
use Carbon\Carbon;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

/**
 * Manuelle Arbeitszeitbuchung ("Arbeitszeit buchen"): Dauer als H:MM ohne 24-h-Grenze,
 * Nachtanteil darf die gebuchte Dauer nicht übersteigen.
 */
final class WorkTimeBookingStoreTest extends FeatureTestCase
{
    private function book(User $target, array $overrides = []): \Illuminate\Testing\TestResponse
    {
        return $this->post(route('users.worktimes.store', $target), array_merge([
            'user_id' => $target->id,
            'date' => '2026-09-25',
            'hours' => '1:00',
            'nightly_working_hours' => '0:00',
            'plus_minus' => '+',
            'comment' => 'Übernahme Stundenkonto',
        ], $overrides));
    }

    #[Test]
    public function durations_beyond_24_hours_can_be_booked(): void
    {
        $target = User::factory()->create(['work_time_balance' => 0]);
        $this->actingAsAdmin(User::factory()->create());

        $this->book($target, ['hours' => '150:30', 'nightly_working_hours' => '30:15'])
            ->assertSuccessful()
            ->assertSessionHasNoErrors();

        $booking = $target->workTimeBookings()->sole();
        $this->assertSame(150 * 60 + 30, (int) $booking->worked_hours);
        $this->assertSame(30 * 60 + 15, (int) $booking->nightly_working_hours);
        $this->assertSame(150 * 60 + 30, (int) $target->fresh()->work_time_balance);
    }

    #[Test]
    public function large_deductions_reduce_the_balance(): void
    {
        $target = User::factory()->create(['work_time_balance' => 0]);
        $this->actingAsAdmin(User::factory()->create());

        $this->book($target, ['hours' => '40:00', 'plus_minus' => '-'])->assertSuccessful();

        $this->assertSame(-2400, (int) $target->fresh()->work_time_balance);
    }

    #[Test]
    public function manual_booking_rebuilds_overtime_immediately(): void
    {
        // Vorher erst mit der nächtlichen Buchung: Überstunden-Tab und Zeitkonto liefen bis dahin auseinander
        $target = User::factory()->create(['work_time_balance' => 0]);
        UserContractAssign::factory()->create([
            'user_id' => $target->id,
            'overtime_rule_active' => true,
            'overtime_compensation_period' => 30,
        ]);
        $this->actingAsAdmin(User::factory()->create());
        $day = Carbon::yesterday()->toDateString();

        $this->book($target, ['date' => $day, 'hours' => '53:00'])->assertSuccessful();
        $this->book($target, ['date' => $day, 'hours' => '10:00'])->assertSuccessful();

        $entry = UserOvertime::where('user_id', $target->id)->sole();
        $this->assertSame($day, $entry->date->toDateString());
        $this->assertSame(63 * 60, $entry->minutes);
        $this->assertSame(63 * 60, (int) $target->fresh()->work_time_balance);
    }

    #[Test]
    #[DataProvider('invalidDurations')]
    public function invalid_durations_are_rejected(array $payload, string $errorField): void
    {
        $target = User::factory()->create(['work_time_balance' => 0]);
        $this->actingAsAdmin(User::factory()->create());

        $this->book($target, $payload)->assertSessionHasErrors($errorField);

        $this->assertSame(0, $target->workTimeBookings()->count());
        $this->assertSame(0, (int) $target->fresh()->work_time_balance);
    }

    public static function invalidDurations(): iterable
    {
        yield 'minutes above 59' => [['hours' => '10:75'], 'hours'];
        yield 'plain number without minutes' => [['hours' => '150'], 'hours'];
        yield 'negative value' => [['hours' => '-5:00'], 'hours'];
        yield 'zero duration' => [['hours' => '0:00'], 'hours'];
        yield 'night exceeds booked hours' => [['hours' => '2:00', 'nightly_working_hours' => '2:30'], 'nightly_working_hours'];
        yield 'date in the future' => [['date' => '2099-01-01'], 'date'];
    }
}
