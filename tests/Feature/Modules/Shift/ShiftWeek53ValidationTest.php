<?php

namespace Tests\Feature\Modules\Shift;

use Artwork\Modules\Craft\Models\Craft;
use Artwork\Modules\GeneralSettings\Models\GeneralSettings;
use Artwork\Modules\Shift\Models\Shift;
use Artwork\Modules\Shift\Models\ShiftPlanRequest;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

/**
 * KW 53 gibt es nur in 53-Wochen-Jahren (2026 ja, 2025 nein). Sammel-Festschreibung (shifts.commit)
 * und Freigabe-Anfrage (commit-shift-workflow-request.store) lehnen eine nicht existierende KW mit
 * 422 ab (IsoWeekExists), statt sie still auf die letzte KW des Jahres zu deckeln.
 */
final class ShiftWeek53ValidationTest extends FeatureTestCase
{
    private function setWorkflowEnabled(bool $enabled): void
    {
        $settings = app(GeneralSettings::class);
        $settings->shift_commit_workflow_enabled = $enabled;
        $settings->save();
    }

    private function shiftOn(Craft $craft, string $date): Shift
    {
        return Shift::factory()->create([
            'craft_id' => $craft->id,
            'start_date' => $date,
            'end_date' => $date,
            'start' => '09:00:00',
            'end' => '17:00:00',
            'is_committed' => false,
            'in_workflow' => false,
            'current_request_id' => null,
        ]);
    }

    #[Test]
    public function bulk_commit_rejects_week_53_in_a_52_week_year(): void
    {
        $this->actingAsAdmin();
        $this->setWorkflowEnabled(false);
        $craft = Craft::factory()->create();
        // Letzte KW 2025 (KW 52) — vorher wurde KW 53/2025 still hierauf gedeckelt
        $shift = $this->shiftOn($craft, '2025-12-23');

        $this->postJson(route('shifts.commit'), [
            'week_number' => 53,
            'year' => 2025,
            'craft_ids' => [$craft->id],
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['week_number']);

        $this->assertFalse($shift->fresh()->is_committed);
    }

    #[Test]
    public function bulk_commit_accepts_week_53_in_a_53_week_year(): void
    {
        $this->actingAsAdmin();
        $this->setWorkflowEnabled(false);
        $craft = Craft::factory()->create();
        // KW 53/2026 = 28.12.2026–03.01.2027
        $shift = $this->shiftOn($craft, '2026-12-30');

        $this->postJson(route('shifts.commit'), [
            'week_number' => 53,
            'year' => 2026,
            'craft_ids' => [$craft->id],
        ])->assertOk();

        $this->assertTrue($shift->fresh()->is_committed);
    }

    #[Test]
    public function shift_plan_request_rejects_week_53_in_a_52_week_year(): void
    {
        $this->actingAsAdmin();
        $craft = Craft::factory()->create();
        $this->shiftOn($craft, '2025-12-23');

        $this->postJson(route('commit-shift-workflow-request.store'), [
            'craft_id' => $craft->id,
            'week_number' => 53,
            'year' => 2025,
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['week_number']);

        $this->assertSame(0, ShiftPlanRequest::query()->count());
    }

    #[Test]
    public function shift_plan_request_accepts_week_53_in_a_53_week_year(): void
    {
        $this->actingAsAdmin();
        $craft = Craft::factory()->create();
        $this->shiftOn($craft, '2026-12-30');

        $this->post(route('commit-shift-workflow-request.store'), [
            'craft_ids' => [$craft->id],
            'week_number' => 53,
            'year' => 2026,
        ])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $this->assertSame(1, ShiftPlanRequest::query()
            ->where('craft_id', $craft->id)
            ->where('week_number', 53)
            ->where('year', 2026)
            ->count());
    }
}
