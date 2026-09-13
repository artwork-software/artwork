<?php

namespace Tests\Unit\Modules\WorkTime;

use Artwork\Modules\Freelancer\Models\Freelancer;
use Artwork\Modules\User\Models\User;
use Artwork\Modules\User\Models\UserWorkTime;
use Artwork\Modules\Vacation\Models\Vacation;
use Artwork\Modules\WorkTime\Services\WorkTimeCalculationService;
use Carbon\Carbon;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Block 4: Das Arbeitszeitmuster ist die einzige Quelle für das Soll. Ohne gültiges Muster ist das
 * Soll UNBEKANNT (null + target_unknown), nicht 0 und nicht weekly_working_hours / 5.
 */
final class WorkTimeTargetUnknownTest extends TestCase
{
    private const MONDAY = '2026-07-20';
    private const TUESDAY = '2026-07-21';

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function service(): WorkTimeCalculationService
    {
        return app(WorkTimeCalculationService::class);
    }

    private function user(): User
    {
        // weekly_working_hours bewusst gesetzt: darf keinen Einfluss mehr haben
        return User::factory()->create(['can_work_shifts' => true, 'weekly_working_hours' => 40]);
    }

    /**
     * @param array<string, string|null> $days
     */
    private function workTime(User $user, array $days, string $validFrom = '2026-01-01', ?string $validUntil = null): void
    {
        UserWorkTime::query()->insert(array_merge([
            'user_id' => $user->id,
            'valid_from' => $validFrom,
            'valid_until' => $validUntil,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ], $days));
    }

    private function individualTime(User $user, string $date, int $minutes): void
    {
        $user->individualTimes()->create([
            'title' => 'Einsatz',
            'start_date' => $date,
            'end_date' => $date,
            'full_day' => true,
            'working_time_minutes' => $minutes,
            'break_minutes' => 0,
        ]);
    }

    #[Test]
    public function without_a_pattern_the_target_is_unknown_and_not_derived_from_weekly_hours(): void
    {
        $user = $this->user();

        $this->assertNull($this->service()->targetMinutes($user, Carbon::parse(self::MONDAY)));

        $day = $this->service()->dayBreakdown($user, Carbon::parse(self::MONDAY));
        $this->assertTrue($day['target_unknown']);
        $this->assertNull($day['target']);
        $this->assertNull($day['base_target']);
        $this->assertNull($day['balance']);
        $this->assertSame(0, $day['actual']);
    }

    #[Test]
    public function without_a_pattern_actual_is_only_the_real_work_even_on_sick_days(): void
    {
        $user = $this->user();
        $this->individualTime($user, self::TUESDAY, 120);
        Vacation::factory()->create([
            'vacationer_type' => User::class,
            'vacationer_id' => $user->id,
            'date' => self::TUESDAY,
            'type' => 'NOT_AVAILABLE',
            'full_day' => true,
            'is_series' => false,
            'comment' => null,
        ]);

        $day = $this->service()->dayBreakdown($user, Carbon::parse(self::TUESDAY));

        $this->assertTrue($day['is_sick']);
        $this->assertTrue($day['target_unknown']);
        $this->assertSame(120, $day['actual']); // kein soll-neutraler Anteil ohne Soll
        $this->assertNull($day['balance']);
    }

    #[Test]
    public function with_a_pattern_the_target_is_unchanged(): void
    {
        $user = $this->user();
        $this->workTime($user, ['monday' => '07:42', 'tuesday' => '08:00']);

        $this->assertSame(462, $this->service()->targetMinutes($user, Carbon::parse(self::MONDAY)));
        $day = $this->service()->dayBreakdown($user, Carbon::parse(self::TUESDAY));
        $this->assertFalse($day['target_unknown']);
        $this->assertSame(480, $day['target']);
        $this->assertSame(-480, $day['balance']);
        // Wochentag ohne Zeit im Muster = frei (0), NICHT unbekannt
        $saturday = $this->service()->dayBreakdown($user, Carbon::parse('2026-07-25'));
        $this->assertFalse($saturday['target_unknown']);
        $this->assertSame(0, $saturday['target']);
    }

    #[Test]
    public function a_range_with_days_without_pattern_is_flagged_and_counts_those_days(): void
    {
        $user = $this->user();
        // Muster erst ab Mittwoch gültig -> Montag + Dienstag ohne Muster
        $this->workTime($user, ['wednesday' => '08:00', 'thursday' => '08:00', 'friday' => '08:00'], '2026-07-22');
        $this->individualTime($user, self::MONDAY, 60);
        $this->individualTime($user, '2026-07-23', 90);

        $range = $this->service()->breakdownForRange($user, Carbon::parse(self::MONDAY), Carbon::parse('2026-07-26'));
        $summary = WorkTimeCalculationService::summarizeRange($range);

        $this->assertTrue($summary['target_unknown']);
        $this->assertSame(2, $summary['days_without_pattern']);
        $this->assertSame(7, $summary['days']);
        $this->assertNull($summary['target']);
        $this->assertNull($summary['balance']);
        $this->assertSame(150, $summary['actual']); // Ist wird immer summiert
        $this->assertSame(3 * 480, $summary['known_target']);

        // Vollständig abgedeckter Teilzeitraum: bekannt
        $covered = WorkTimeCalculationService::summarizeRange(
            $this->service()->breakdownForRange($user, Carbon::parse('2026-07-22'), Carbon::parse('2026-07-26'))
        );
        $this->assertFalse($covered['target_unknown']);
        $this->assertSame(0, $covered['days_without_pattern']);
        $this->assertSame(3 * 480, $covered['target']);
        $this->assertSame(90 - 3 * 480, $covered['balance']);
    }

    #[Test]
    public function base_targets_for_range_return_null_on_days_without_pattern(): void
    {
        $user = $this->user();
        $this->workTime($user, ['monday' => '08:00'], '2026-07-21');

        $targets = $this->service()->baseTargetsForRange($user, Carbon::parse(self::MONDAY), Carbon::parse('2026-07-27'));

        $this->assertNull($targets[self::MONDAY]);
        $this->assertSame(0, $targets[self::TUESDAY]); // Muster gültig, Dienstag ohne Zeit
        $this->assertSame(480, $targets['2026-07-27']);
    }

    #[Test]
    public function externals_have_no_target_and_are_never_unknown(): void
    {
        $freelancer = Freelancer::factory()->create();

        $day = $this->service()->dayBreakdown($freelancer, Carbon::parse(self::MONDAY));

        $this->assertSame(0, $day['target']);
        $this->assertFalse($day['target_unknown']);
    }

    #[Test]
    public function current_weekly_hours_come_from_the_pattern_valid_today(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-07-21 12:00:00'));
        $user = $this->user();

        $this->assertNull($this->service()->currentWeeklyHours($user));

        $this->workTime($user, [
            'monday' => '07:42', 'tuesday' => '07:42', 'wednesday' => '07:42', 'thursday' => '07:42', 'friday' => '07:42',
        ], '2026-01-01', '2026-06-30'); // abgelaufen
        $this->assertNull($this->service()->currentWeeklyHours($user));

        $this->workTime($user, [
            'monday' => '07:42', 'tuesday' => '07:42', 'wednesday' => '07:42', 'thursday' => '07:42', 'friday' => '07:42',
        ], '2026-07-01');
        $this->assertSame(38.5, $this->service()->currentWeeklyHours($user));
    }

    #[Test]
    public function user_ids_with_pattern_on_a_day_are_resolved_in_one_query(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-07-21 12:00:00'));
        $withPattern = $this->user();
        $expired = $this->user();
        $future = $this->user();
        $without = $this->user();
        $this->workTime($withPattern, ['monday' => '08:00'], '2026-01-01');
        $this->workTime($expired, ['monday' => '08:00'], '2026-01-01', '2026-06-30');
        $this->workTime($future, ['monday' => '08:00'], '2026-08-01');

        \Illuminate\Support\Facades\DB::flushQueryLog();
        \Illuminate\Support\Facades\DB::enableQueryLog();
        $result = $this->service()->userIdsWithPatternOn([$withPattern->id, $expired->id, $future->id, $without->id]);
        $queries = count(\Illuminate\Support\Facades\DB::getQueryLog());
        \Illuminate\Support\Facades\DB::disableQueryLog();

        $this->assertSame(1, $queries);
        $this->assertSame([$withPattern->id => true], $result);
        $this->assertSame([], $this->service()->userIdsWithPatternOn([]));
    }
}
