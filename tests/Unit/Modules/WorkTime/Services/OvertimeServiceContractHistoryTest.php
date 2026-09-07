<?php

namespace Tests\Unit\Modules\WorkTime\Services;

use Artwork\Modules\User\Models\User;
use Artwork\Modules\User\Models\UserContract;
use Artwork\Modules\User\Models\UserContractAssign;
use Artwork\Modules\WorkTime\Models\UserOvertime;
use Artwork\Modules\WorkTime\Models\WorkTimeBooking;
use Artwork\Modules\WorkTime\Services\OvertimeService;
use Carbon\Carbon;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Härtung: OvertimeService liest Überstundenregel und Abbaufrist je Buchungstag aus dem an diesem Tag
 * gültigen Vertragszeitraum (Historie), nicht aus dem heute gültigen Satz. Eine Lücke "heute" bricht
 * den Replay nicht ab.
 */
final class OvertimeServiceContractHistoryTest extends TestCase
{
    private OvertimeService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(OvertimeService::class);
        // Fester Stichtag in der Lücke zwischen Zeitraum A (bis 31.08.) und B (ab 01.10.)
        Carbon::setTestNow(Carbon::parse('2026-09-07 10:00:00'));
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function template(array $attributes = []): UserContract
    {
        return UserContract::factory()->create(array_merge([
            'overtime_rule_active' => false,
            'overtime_compensation_period' => null,
        ], $attributes));
    }

    private function assign(User $user, UserContract $template, array $attributes = []): UserContractAssign
    {
        return UserContractAssign::factory()->create(array_merge([
            'user_id' => $user->id,
            'user_contract_id' => $template->id,
            'overtime_rule_active' => true,
            'overtime_compensation_period' => 30,
        ], $attributes));
    }

    private function booking(User $user, string $day, int $balanceChange): void
    {
        $date = Carbon::parse($day);
        WorkTimeBooking::create([
            'user_id' => $user->id,
            'name' => 'test_' . $date->toDateString(),
            'booking_day' => $date->toDateString(),
            'booking_weekday' => $date->dayOfWeek,
            'wanted_working_hours' => 0,
            'worked_hours' => max(0, $balanceChange),
            'work_time_balance_change' => $balanceChange,
        ]);
    }

    #[Test]
    public function entries_follow_the_contract_period_of_the_booking_day_and_a_gap_today_does_not_abort(): void
    {
        $user = User::factory()->create();
        $template = $this->template();
        // Zeitraum A: aktiv, Frist 30 Tage, bis 31.08. – Lücke im September – Zeitraum B ab 01.10., Frist 60 Tage
        $this->assign($user, $template, ['valid_from' => null, 'valid_until' => '2026-08-31', 'overtime_compensation_period' => 30]);
        $this->assign($user, $template, ['valid_from' => '2026-10-01', 'valid_until' => null, 'overtime_compensation_period' => 60]);

        $this->booking($user, '2026-08-10', 120);
        $this->booking($user, '2026-08-20', 60);
        $this->booking($user, '2026-09-05', 90); // Lücke: keine Regel → kein Eintrag

        // Heute (07.09.) gilt kein Zeitraum – früher brach recomputeForUser hier ab
        $this->assertNull($user->fresh()->contract);

        $this->service->recomputeForUser($user->fresh());

        $entries = UserOvertime::where('user_id', $user->id)->orderBy('date')->get();
        $this->assertSame(['2026-08-10', '2026-08-20'], $entries->map(fn (UserOvertime $e) => $e->date->toDateString())->all());
        $this->assertSame('2026-09-09', $entries[0]->deadline->toDateString());
        $this->assertSame('2026-09-19', $entries[1]->deadline->toDateString());
        $this->assertSame(120, $entries[0]->minutes);
        $this->assertSame(60, $entries[1]->minutes);
        $this->assertSame(0, UserOvertime::where('user_id', $user->id)->whereDate('date', '2026-09-05')->count());
    }

    #[Test]
    public function the_deadline_uses_the_period_valid_on_the_booking_day(): void
    {
        $user = User::factory()->create();
        $template = $this->template();
        $this->assign($user, $template, ['valid_from' => null, 'valid_until' => '2026-06-30', 'overtime_compensation_period' => 10]);
        $this->assign($user, $template, ['valid_from' => '2026-07-01', 'valid_until' => null, 'overtime_compensation_period' => 90]);

        $this->booking($user, '2026-06-15', 30);
        $this->booking($user, '2026-07-15', 30);

        $this->service->recomputeForUser($user->fresh());

        $june = UserOvertime::where('user_id', $user->id)->whereDate('date', '2026-06-15')->firstOrFail();
        $july = UserOvertime::where('user_id', $user->id)->whereDate('date', '2026-07-15')->firstOrFail();
        // 10 Tage Frist, am 07.09. abgelaufen → auszahlbar; 90 Tage Frist → noch offen
        $this->assertSame('2026-06-25', $june->deadline->toDateString());
        $this->assertSame(UserOvertime::STATUS_PAYABLE, $june->status);
        $this->assertSame('2026-10-13', $july->deadline->toDateString());
        $this->assertSame(UserOvertime::STATUS_OPEN, $july->status);
    }

    #[Test]
    public function days_in_a_period_without_active_rule_create_no_entries_but_other_periods_do(): void
    {
        $user = User::factory()->create();
        $template = $this->template();
        $this->assign($user, $template, ['valid_from' => null, 'valid_until' => '2026-05-31', 'overtime_rule_active' => false]);
        $this->assign($user, $template, ['valid_from' => '2026-06-01', 'valid_until' => null, 'overtime_rule_active' => true]);

        $this->booking($user, '2026-05-20', 45);
        $this->booking($user, '2026-06-20', 45);

        $this->service->recomputeForUser($user->fresh());

        $this->assertSame(
            ['2026-06-20'],
            UserOvertime::where('user_id', $user->id)->orderBy('date')->get()->map(fn (UserOvertime $e) => $e->date->toDateString())->all()
        );
    }

    #[Test]
    public function a_missing_period_on_the_assignment_falls_back_to_the_template(): void
    {
        $user = User::factory()->create();
        // Frist nur auf der Vorlage (Zuweisung: overtime_compensation_period null = nicht gesetzt)
        $template = $this->template(['overtime_compensation_period' => 45]);
        $this->assign($user, $template, ['valid_from' => null, 'valid_until' => null, 'overtime_compensation_period' => null]);

        $this->booking($user, '2026-09-01', 30);

        $this->service->recomputeForUser($user->fresh());

        $entry = UserOvertime::where('user_id', $user->id)->firstOrFail();
        $this->assertSame('2026-10-16', $entry->deadline->toDateString());
    }

    #[Test]
    public function without_any_contract_assignment_nothing_is_written(): void
    {
        $user = User::factory()->create();
        $this->booking($user, '2026-09-01', 30);

        $this->service->recomputeForUser($user->fresh());

        $this->assertSame(0, UserOvertime::where('user_id', $user->id)->count());
    }
}
