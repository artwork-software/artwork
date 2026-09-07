<?php

namespace Tests\Unit\Modules\WorkTime\Services;

use Artwork\Modules\GeneralSettings\Models\GeneralSettings;
use Artwork\Modules\IndividualTimes\Models\IndividualTime;
use Artwork\Modules\Shift\Models\Shift;
use Artwork\Modules\Shift\Models\ShiftQualification;
use Artwork\Modules\Shift\Models\ShiftWorker;
use Artwork\Modules\User\Models\User;
use Artwork\Modules\WorkTime\Services\WorkTimeBookingService;
use Carbon\Carbon;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Nachtminuten je Buchungstag: Schichten zählen zu ihrem Starttag (inkl. Anteil nach Mitternacht),
 * Frühschichten zählen den Anteil vor Nachtende, individuelle Zeiten werden je Kalendertag zugeschnitten.
 * Vorher wurde jede Arbeit auf 24:00 gekappt, der Anteil nach Mitternacht zählte nie.
 */
final class WorkTimeBookingNightMinutesTest extends TestCase
{
    private WorkTimeBookingService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $settings = app(GeneralSettings::class);
        $settings->start_night_time = '22:00';
        $settings->end_night_time = '06:00';
        $settings->save();
        $this->service = app(WorkTimeBookingService::class);
    }

    private function nightMinutes(User $user, string $day): int
    {
        $user->load(['shifts', 'individualTimes']);
        $method = new \ReflectionMethod(WorkTimeBookingService::class, 'calculateNightMinutes');
        $method->setAccessible(true);

        return (int) $method->invoke($this->service, Carbon::parse($day), $user);
    }

    private function assign(User $user, string $startDate, string $start, string $endDate, string $end): void
    {
        $shift = Shift::factory()->create([
            'start_date' => $startDate,
            'end_date' => $endDate,
            'start' => $start,
            'end' => $end,
        ]);
        ShiftWorker::create([
            'shift_id' => $shift->id,
            'employable_type' => User::class,
            'employable_id' => $user->id,
            'shift_qualification_id' => ShiftQualification::factory()->create()->id,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'start_time' => $start,
            'end_time' => $end,
        ]);
    }

    #[Test]
    public function a_shift_across_midnight_counts_its_night_minutes_on_the_start_day_only(): void
    {
        $user = User::factory()->create();
        $this->assign($user, '2026-09-10', '22:00:00', '2026-09-11', '02:00:00');

        $this->assertSame(240, $this->nightMinutes($user, '2026-09-10'));
        $this->assertSame(0, $this->nightMinutes($user, '2026-09-11'));
    }

    #[Test]
    public function an_early_shift_counts_the_part_before_night_end(): void
    {
        $user = User::factory()->create();
        $this->assign($user, '2026-09-10', '04:00:00', '2026-09-10', '12:00:00');

        $this->assertSame(120, $this->nightMinutes($user, '2026-09-10'));
    }

    #[Test]
    public function a_day_shift_has_no_night_minutes(): void
    {
        $user = User::factory()->create();
        $this->assign($user, '2026-09-10', '09:00:00', '2026-09-10', '17:00:00');

        $this->assertSame(0, $this->nightMinutes($user, '2026-09-10'));
    }

    #[Test]
    public function an_individual_time_across_midnight_is_split_per_calendar_day(): void
    {
        $user = User::factory()->create();
        IndividualTime::create([
            'timeable_type' => User::class,
            'timeable_id' => $user->id,
            'title' => 'Nachtprobe',
            'start_date' => '2026-09-10',
            'end_date' => '2026-09-11',
            'start_time' => '23:00',
            'end_time' => '01:00',
            'full_day' => false,
            'working_time_minutes' => 120,
            'break_minutes' => 0,
            'days_of_individual_time' => ['2026-09-10', '2026-09-11'],
        ]);

        $this->assertSame(60, $this->nightMinutes($user, '2026-09-10'));
        $this->assertSame(60, $this->nightMinutes($user, '2026-09-11'));
    }
}
