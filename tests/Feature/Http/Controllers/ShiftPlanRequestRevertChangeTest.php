<?php

namespace Tests\Feature\Http\Controllers;

use Artwork\Modules\Craft\Models\Craft;
use Artwork\Modules\Shift\Models\Shift;
use Artwork\Modules\Shift\Models\ShiftPlanRequest;
use Artwork\Modules\Shift\Models\ShiftPlanRequestChange;
use Artwork\Modules\Shift\Models\ShiftQualification;
use Artwork\Modules\Shift\Models\ShiftsQualifications;
use Artwork\Modules\Shift\Models\ShiftUser;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

/**
 * Funktion "Einzeländerung einer Freigabeanfrage zurückdrehen" (Rechte deckt die Rollen-Matrix ab).
 */
final class ShiftPlanRequestRevertChangeTest extends FeatureTestCase
{
    private Craft $craft;

    private ShiftPlanRequest $shiftPlanRequest;

    private Shift $shift;

    protected function setUp(): void
    {
        parent::setUp();

        $admin = $this->actingAsAdmin();
        $this->craft = Craft::factory()->create();
        $this->shiftPlanRequest = $this->createRequest($admin->id);
        $this->shift = Shift::factory()->create([
            'craft_id' => $this->craft->id,
            'start' => '10:00:00',
            'end' => '18:00:00',
            'break_minutes' => 30,
            'start_date' => '2026-05-06',
            'end_date' => '2026-05-06',
            'in_workflow' => true,
            'current_request_id' => $this->shiftPlanRequest->id,
        ]);
    }

    #[Test]
    public function field_change_is_rolled_back_and_change_entry_removed(): void
    {
        $change = $this->createChange([
            'start' => ['old' => '08:00', 'new' => '10:00'],
            'break_minutes' => ['old' => 60, 'new' => 30],
        ]);

        $changesBefore = ShiftPlanRequestChange::query()->where('subject_id', $this->shift->id)->count();

        $this->patch($this->revertRoute($change))->assertRedirect()->assertSessionHasNoErrors();

        $fresh = $this->shift->fresh();
        $this->assertSame('08:00', substr((string) $fresh->getRawOriginal('start'), 0, 5));
        $this->assertSame(60, (int) $fresh->break_minutes);
        $this->assertNull($change->fresh());
        // Das Zurückdrehen selbst darf keinen neuen Änderungseintrag erzeugen
        $this->assertSame(
            $changesBefore - 1,
            ShiftPlanRequestChange::query()->where('subject_id', $this->shift->id)->count()
        );
    }

    #[Test]
    public function qualification_demand_is_rolled_back(): void
    {
        $qualification = ShiftQualification::factory()->create();
        $demand = ShiftsQualifications::query()->create([
            'shift_id' => $this->shift->id,
            'shift_qualification_id' => $qualification->id,
            'value' => 5,
        ]);
        $change = $this->createChange([
            'qualifications' => [
                ['qualification_id' => $qualification->id, 'label' => 'Licht', 'old' => 2, 'new' => 5, 'kind' => 'normal'],
            ],
        ]);

        $this->patch($this->revertRoute($change))->assertRedirect()->assertSessionHasNoErrors();

        $this->assertSame(2, (int) $demand->fresh()->value);
        $this->assertNull($change->fresh());
    }

    #[Test]
    public function change_of_another_request_is_refused(): void
    {
        $otherRequest = $this->createRequest($this->shiftPlanRequest->requested_by_user_id);
        $change = $this->createChange(['start' => ['old' => '08:00', 'new' => '10:00']]);

        $this->patch(route('shift-plan-requests.change.revert', [$otherRequest, $change]))
            ->assertRedirect()
            ->assertSessionHasErrors('message');

        $this->assertSame('10:00', substr((string) $this->shift->fresh()->getRawOriginal('start'), 0, 5));
        $this->assertNotNull($change->fresh());
    }

    #[Test]
    public function pivot_changes_are_not_revertable(): void
    {
        $change = $this->createChange(['start' => ['old' => '08:00', 'new' => '10:00']], ShiftUser::class);

        $this->patch($this->revertRoute($change))->assertRedirect()->assertSessionHasErrors('message');

        $this->assertNotNull($change->fresh());
    }

    #[Test]
    public function change_of_deleted_shift_or_without_field_changes_reports_error(): void
    {
        $emptyChange = $this->createChange([]);
        $this->patch($this->revertRoute($emptyChange))->assertRedirect()->assertSessionHasErrors('message');
        $this->assertNotNull($emptyChange->fresh());

        $orphanChange = $this->createChange(['start' => ['old' => '08:00', 'new' => '10:00']]);
        $orphanChange->update(['subject_id' => 99999999]);
        $this->patch($this->revertRoute($orphanChange))->assertRedirect()->assertSessionHasErrors('message');
        $this->assertNotNull($orphanChange->fresh());
    }

    #[Test]
    public function shift_outside_the_request_is_refused(): void
    {
        $foreignShift = Shift::factory()->create([
            'craft_id' => $this->craft->id,
            'start' => '10:00:00',
            'end' => '18:00:00',
            'current_request_id' => null,
        ]);
        $change = $this->createChange(['start' => ['old' => '08:00', 'new' => '10:00']]);
        $change->update(['subject_id' => $foreignShift->id]);

        $this->patch($this->revertRoute($change))->assertRedirect()->assertSessionHasErrors('message');

        $this->assertSame('10:00', substr((string) $foreignShift->fresh()->getRawOriginal('start'), 0, 5));
    }

    /**
     * @param array<string, mixed> $fieldChanges
     */
    private function createChange(array $fieldChanges, string $subjectType = Shift::class): ShiftPlanRequestChange
    {
        return ShiftPlanRequestChange::query()->create([
            'shift_plan_request_id' => $this->shiftPlanRequest->id,
            'subject_type' => $subjectType,
            'subject_id' => $this->shift->id,
            'change_type' => 'updated',
            'field_changes' => $fieldChanges,
            'changed_at' => now(),
        ]);
    }

    private function createRequest(int $requestedBy): ShiftPlanRequest
    {
        return ShiftPlanRequest::query()->create([
            'craft_id' => $this->craft->id,
            'week_number' => 19,
            'year' => 2026,
            'status' => 'pending',
            'requested_by_user_id' => $requestedBy,
            'requested_at' => now(),
        ]);
    }

    private function revertRoute(ShiftPlanRequestChange $change): string
    {
        return route('shift-plan-requests.change.revert', [$this->shiftPlanRequest, $change]);
    }
}
