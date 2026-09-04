<?php

namespace Tests\Feature\Http\Controllers;

use Artwork\Modules\Craft\Models\Craft;
use Artwork\Modules\Permission\Enums\PermissionEnum;
use Artwork\Modules\User\Models\User;
use Artwork\Modules\WorkTime\Services\WorkTimeCalculationService;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Permission;
use Tests\Feature\FeatureTestCase;

/**
 * KW-Spalte im Dienstplan: der Worker-Payload liefert neben den bisherigen Keys
 * (daily_target/planned/difference im Format "2h 0m") die *_formatted-Keys im einheitlichen
 * Format "H:MM h" (Differenz signiert mit + bzw. echtem Minus U+2212).
 */
final class ShiftPlanWeeklyHoursFormatTest extends FeatureTestCase
{
    private User $viewer;

    private Craft $craft;

    protected function setUp(): void
    {
        parent::setUp();

        $this->viewer = User::factory()->create([
            'can_work_shifts' => true,
            'weekly_working_hours' => 40,
            'work_time_balance' => 0,
        ]);
        $this->craft = Craft::factory()->create();
        $this->viewer->assignedCrafts()->attach($this->craft->id);

        foreach ([PermissionEnum::VIEW_SHIFT_PLAN, PermissionEnum::CAN_VIEW_SHIFT_WORKER_HOURS] as $permission) {
            Permission::query()->firstOrCreate(['name' => $permission->value, 'guard_name' => 'web']);
            $this->viewer->givePermissionTo($permission->value);
        }
    }

    #[Test]
    public function worker_payload_contains_formatted_weekly_hours(): void
    {
        $start = now()->startOfWeek();
        $end = now()->endOfWeek();

        $usersForShifts = $this->actingAs($this->viewer)
            ->getJson(route('shifts.workers', [
                'start_date' => $start->toDateString(),
                'end_date' => $end->toDateString(),
                'craft_ids' => [$this->craft->id],
            ]))
            ->assertOk()
            ->json('usersForShifts');

        $entry = collect($usersForShifts)->firstWhere('user.id', $this->viewer->id);
        $this->assertNotNull($entry, 'Viewer fehlt im usersForShifts-Payload.');

        $weekNumber = ltrim($start->format('W'), '0');
        $week = $entry['weeklyWorkingHours'][$weekNumber] ?? null;
        $this->assertIsArray($week, 'KW-Eintrag fehlt im weeklyWorkingHours-Payload.');

        // Alte Keys bleiben (ShiftPlanWorkerHoursPermissionTest, Freelancer-/Dienstleister-Ansichten)
        $this->assertMatchesRegularExpression('/^\d+h \d+m$/', $week['planned']);
        $this->assertMatchesRegularExpression('/^\d+h \d+m$/', $week['daily_target']);
        $this->assertMatchesRegularExpression('/^-?\d+h \d+m$/', $week['difference']);

        // Neue, einheitlich formatierte Keys "H:MM h"
        $this->assertMatchesRegularExpression('/^\d+:\d{2} h$/u', $week['planned_formatted']);
        $this->assertMatchesRegularExpression('/^\d+:\d{2} h$/u', $week['daily_target_formatted']);
        $this->assertMatchesRegularExpression('/^[+\x{2212}]\d+:\d{2} h$/u', $week['difference_formatted']);

        // Formatierte Keys entsprechen exakt den Rohminuten (Sondertage/Abwesenheiten in der
        // Test-DB können das Soll verändern, daher relativ statt gegen feste 40:00 h prüfen)
        $this->assertSame(WorkTimeCalculationService::formatHours((int) $week['target_minutes']), $week['daily_target_formatted']);
        $this->assertSame(WorkTimeCalculationService::formatHours((int) $week['planned_minutes']), $week['planned_formatted']);
        $this->assertSame(
            WorkTimeCalculationService::formatSignedHours((int) $week['difference_minutes']),
            $week['difference_formatted']
        );
        $this->assertSame('0:00 h', $week['planned_formatted']);
        $this->assertSame($week['difference_minutes'] < 0, $week['isMinus']);
    }
}
