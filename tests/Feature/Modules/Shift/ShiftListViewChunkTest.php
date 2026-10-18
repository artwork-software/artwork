<?php

namespace Tests\Feature\Modules\Shift;

use Artwork\Core\Http\Middleware\HandleInertiaRequests;
use Artwork\Modules\Event\Models\Event;
use Artwork\Modules\Permission\Enums\PermissionEnum;
use Artwork\Modules\Shift\Models\Shift;
use Artwork\Modules\Shift\Services\ShiftListViewService;
use Artwork\Modules\User\Enums\UserFilterTypes;
use Artwork\Modules\User\Models\User;
use Artwork\Modules\User\Models\UserShiftListViewSettings;
use Carbon\Carbon;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

/**
 * Die Dienstplan-Listenansicht lädt wochenweise: die Seite liefert den ersten Block, weitere Tage kommen
 * über shifts.plan.list-view.days. Die Vereinigung der Blöcke muss exakt dem Gesamtergebnis entsprechen —
 * auch für Schichten, die vor dem Zeitraum beginnen, mehrtägige Schichten und Termine über Blockgrenzen.
 */
final class ShiftListViewChunkTest extends FeatureTestCase
{
    private const START = '2026-07-01';
    private const END = '2026-07-20';

    private function settings(): UserShiftListViewSettings
    {
        return new UserShiftListViewSettings([
            'show_appointments' => true,
            'show_fully_staffed_shifts' => true,
        ]);
    }

    private function seedPeriod(): void
    {
        foreach (['2026-07-01', '2026-07-07', '2026-07-08', '2026-07-14', '2026-07-15', '2026-07-20'] as $day) {
            Shift::factory()->create(['start_date' => $day, 'end_date' => $day, 'event_start_day' => $day,
                'event_end_day' => $day, 'event_id' => null]);
        }
        // beginnt vor dem Zeitraum, endet darin
        Shift::factory()->create(['start_date' => '2026-06-29', 'end_date' => '2026-07-02',
            'event_start_day' => '2026-06-29', 'event_end_day' => '2026-07-02', 'event_id' => null]);
        // über eine Blockgrenze (07.→08.)
        Shift::factory()->create(['start_date' => '2026-07-07', 'end_date' => '2026-07-09',
            'event_start_day' => '2026-07-07', 'event_end_day' => '2026-07-09', 'event_id' => null]);
        // Termin über mehrere Blöcke
        Event::factory()->create(['start_time' => '2026-07-06 10:00:00', 'end_time' => '2026-07-16 12:00:00']);
    }

    /**
     * @param array<int, array{day: string}> $local
     * @param array<int, array{day: string}> $chunkDays
     * @return array<int, array{day: string}>
     */
    private function merge(array $local, array $chunkDays, ?string $from, ?string $to): array
    {
        $owned = static fn (array $d): bool => ($from === null || $d['day'] >= $from) && ($to === null || $d['day'] <= $to);
        $merged = array_merge(
            array_values(array_filter($local, static fn (array $d): bool => !$owned($d))),
            array_values(array_filter($chunkDays, $owned)),
        );
        usort($merged, static fn (array $a, array $b): int => strcmp($a['day'], $b['day']));

        return $merged;
    }

    #[Test]
    public function weekly_chunks_add_up_to_the_full_period(): void
    {
        $this->seedPeriod();
        $service = app(ShiftListViewService::class);
        $start = Carbon::parse(self::START);
        $end = Carbon::parse(self::END);

        $full = json_decode(json_encode($service->getGroupedShifts($start, $end, $this->settings())), true);

        $local = [];
        for ($chunkStart = $start->copy(); $chunkStart->lte($end); $chunkStart->addDays(7)) {
            $chunkEnd = $chunkStart->copy()->addDays(6)->min($end);
            $days = json_decode(json_encode($service->getGroupedShifts(
                $start,
                $end,
                $this->settings(),
                null,
                $chunkStart->copy(),
                $chunkEnd->copy()
            )), true);
            $local = $this->merge(
                $local,
                $days,
                $chunkStart->eq($start) ? null : $chunkStart->toDateString(),
                $chunkEnd->eq($end) ? null : $chunkEnd->toDateString()
            );
        }

        $this->assertNotEmpty($full);
        $this->assertSame('2026-06-29', $full[0]['day']);
        $this->assertSame(json_encode($full), json_encode($local));
    }

    private function plannerWithPeriod(): User
    {
        $user = $this->actingAsUserWith([PermissionEnum::VIEW_SHIFT_PLAN->value]);
        $user->userFilters()->updateOrCreate(
            ['filter_type' => UserFilterTypes::SHIFT_LIST_VIEW_FILTER->value],
            ['start_date' => self::START, 'end_date' => self::END]
        );

        return $user;
    }

    #[Test]
    public function the_page_delivers_only_the_first_week(): void
    {
        $this->seedPeriod();
        $this->plannerWithPeriod();

        $props = $this->get(route('shifts.plan.list-view'), [
            'X-Inertia' => 'true',
            'X-Inertia-Version' => app(HandleInertiaRequests::class)->version(request()),
        ])->assertOk()->json('props');

        $this->assertSame(
            ['start' => self::START, 'end' => self::END, 'loadedUntil' => '2026-07-07', 'chunkDays' => 7],
            $props['groupedShiftsChunk']
        );
        $days = array_column($props['groupedShifts'], 'day');
        $this->assertContains('2026-07-07', $days);
        $this->assertContains('2026-06-29', $days);
        $this->assertNotContains('2026-07-08', $days);
    }

    #[Test]
    public function the_days_endpoint_delivers_a_chunk_within_the_saved_period(): void
    {
        $this->seedPeriod();
        $this->plannerWithPeriod();

        $response = $this->getJson(route('shifts.plan.list-view.days', [
            'chunk_start' => '2026-07-08',
            'chunk_end' => '2026-07-14',
        ]))->assertOk();

        $this->assertSame(self::START, $response->json('start'));
        $this->assertSame(self::END, $response->json('end'));
        $this->assertSame('2026-07-08', $response->json('chunkStart'));
        $this->assertSame('2026-07-14', $response->json('chunkEnd'));
        $days = array_column($response->json('groupedShifts'), 'day');
        // Schichttage des Blocks (Termine sind in den Standard-Einstellungen ausgeblendet)
        $this->assertSame(['2026-07-08', '2026-07-14'], $days);
    }

    #[Test]
    public function the_days_endpoint_rejects_chunks_outside_the_period_and_validates_input(): void
    {
        $this->plannerWithPeriod();

        $this->getJson(route('shifts.plan.list-view.days', ['chunk_start' => '2026-08-01', 'chunk_end' => '2026-08-07']))
            ->assertStatus(422);
        $this->getJson(route('shifts.plan.list-view.days', ['chunk_start' => '2026-07-10', 'chunk_end' => '2026-07-01']))
            ->assertStatus(422);
        $this->getJson(route('shifts.plan.list-view.days'))->assertStatus(422);
    }

    #[Test]
    public function the_days_endpoint_requires_the_shift_plan_permission(): void
    {
        $this->actingAs(User::factory()->create());

        $this->getJson(route('shifts.plan.list-view.days', ['chunk_start' => self::START, 'chunk_end' => self::END]))
            ->assertForbidden();
    }
}
