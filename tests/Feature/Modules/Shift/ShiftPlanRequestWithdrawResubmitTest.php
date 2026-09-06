<?php

namespace Tests\Feature\Modules\Shift;

use Artwork\Modules\Craft\Models\Craft;
use Artwork\Modules\Permission\Enums\PermissionEnum;
use Artwork\Modules\Shift\Models\Shift;
use Artwork\Modules\Shift\Models\ShiftPlanRequest;
use Artwork\Modules\Shift\Notifications\ShiftNotification;
use Artwork\Modules\User\Models\User;
use Illuminate\Support\Facades\Notification;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

/**
 * Block 3a: Antragsteller*innen dürfen ihre eigene offene Freigabe-Anfrage zurückziehen
 * (ShiftPlanRequestPolicy::withdraw), nach einer Ablehnung erneut einreichen (neue Anfrage,
 * abgelehnte Schichten werden wieder eingesammelt), und die Ablehnung nennt den Rollback.
 */
final class ShiftPlanRequestWithdrawResubmitTest extends FeatureTestCase
{
    private function createCraftAndShift(): array
    {
        // 2026-05-06 liegt in ISO-KW 19 (Mo 2026-05-04 – So 2026-05-10)
        $craft = Craft::factory()->create();
        $shift = Shift::factory()->create([
            'craft_id' => $craft->id,
            'start_date' => '2026-05-06',
            'end_date' => '2026-05-06',
            'start' => '09:00:00',
            'end' => '17:00:00',
            'in_workflow' => false,
            'current_request_id' => null,
        ]);

        return [$craft, $shift];
    }

    private function submitRequest(Craft $craft): ShiftPlanRequest
    {
        $this->post(route('commit-shift-workflow-request.store'), [
            'craft_id' => $craft->id,
            'week_number' => 19,
            'year' => 2026,
        ])->assertRedirect();

        return ShiftPlanRequest::query()
            ->where('craft_id', $craft->id)
            ->where('status', 'pending')
            ->latest('id')
            ->firstOrFail();
    }

    #[Test]
    public function requester_can_withdraw_own_pending_request(): void
    {
        $requester = $this->actingAsUserWith([PermissionEnum::CAN_COMMIT_SHIFTS->value]);
        [$craft, $shift] = $this->createCraftAndShift();
        $request = $this->submitRequest($craft);
        $this->assertEquals($requester->id, $request->requested_by_user_id);

        $this->delete(route('shift-plan-requests.destroy', $request))->assertRedirect();

        $this->assertDatabaseMissing('shift_plan_requests', ['id' => $request->id]);
        $shift->refresh();
        $this->assertNull($shift->current_request_id);
        $this->assertFalse((bool) $shift->in_workflow);
    }

    #[Test]
    public function withdrawing_sends_no_notification_to_approvers(): void
    {
        $approver = User::factory()->create(['language' => 'de']);
        \Artwork\Modules\Shift\Models\ShiftCommitWorkflowUser::create(['user_id' => $approver->id]);
        $this->actingAsUserWith([PermissionEnum::CAN_COMMIT_SHIFTS->value]);
        [$craft] = $this->createCraftAndShift();
        $request = $this->submitRequest($craft);

        // Die Neuanlage benachrichtigt Genehmiger*innen — danach darf beim Zurückziehen nichts dazukommen
        $sentBefore = Notification::sent($approver, ShiftNotification::class)->count();

        $this->delete(route('shift-plan-requests.destroy', $request))->assertRedirect();

        $this->assertSame($sentBefore, Notification::sent($approver, ShiftNotification::class)->count());
    }

    #[Test]
    public function other_person_cannot_withdraw_a_foreign_request(): void
    {
        $this->actingAsUserWith([PermissionEnum::CAN_COMMIT_SHIFTS->value]);
        [$craft] = $this->createCraftAndShift();
        $request = $this->submitRequest($craft);

        $this->actingAsUserWith([PermissionEnum::CAN_COMMIT_SHIFTS->value]);

        $this->delete(route('shift-plan-requests.destroy', $request))->assertForbidden();
        $this->assertDatabaseHas('shift_plan_requests', ['id' => $request->id, 'status' => 'pending']);
    }

    #[Test]
    public function requester_cannot_withdraw_an_approved_request(): void
    {
        $requester = $this->actingAsUserWith([PermissionEnum::CAN_COMMIT_SHIFTS->value]);
        [$craft] = $this->createCraftAndShift();
        $request = $this->submitRequest($craft);

        $this->actingAsAdmin();
        $this->post(route('shift-plan-requests.accept', $request))->assertRedirect();
        $this->assertSame('approved', $request->fresh()->status);

        $this->actingAs($requester);
        $this->delete(route('shift-plan-requests.destroy', $request))->assertForbidden();
        $this->assertDatabaseHas('shift_plan_requests', ['id' => $request->id, 'status' => 'approved']);
    }

    #[Test]
    public function approver_can_still_withdraw_a_pending_request(): void
    {
        $this->actingAsUserWith([PermissionEnum::CAN_COMMIT_SHIFTS->value]);
        [$craft] = $this->createCraftAndShift();
        $request = $this->submitRequest($craft);

        $this->actingAsAdmin();
        $this->delete(route('shift-plan-requests.destroy', $request))->assertRedirect();

        $this->assertDatabaseMissing('shift_plan_requests', ['id' => $request->id]);
    }

    #[Test]
    public function resubmitting_after_rejection_creates_a_new_request_with_the_rejected_shifts(): void
    {
        $requester = $this->actingAsUserWith([PermissionEnum::CAN_COMMIT_SHIFTS->value]);
        [$craft, $shift] = $this->createCraftAndShift();
        $firstRequest = $this->submitRequest($craft);

        $this->actingAsAdmin();
        $this->post(route('shift-plan-requests.reject', $firstRequest), [
            'global_reason' => 'Bitte Pausen korrigieren',
        ])->assertRedirect();
        $this->assertSame('rejected', $firstRequest->fresh()->status);

        $this->actingAs($requester);
        $response = $this->post(route('commit-shift-workflow-request.store'), [
            'craft_id' => $craft->id,
            'week_number' => 19,
            'year' => 2026,
        ]);
        $response->assertRedirect();
        $response->assertSessionHas(
            'success',
            __('Shift plan request for week :week resubmitted.', ['week' => 19])
        );
        $response->assertSessionHas('resubmitted', true);

        $secondRequest = ShiftPlanRequest::query()
            ->where('craft_id', $craft->id)
            ->where('status', 'pending')
            ->firstOrFail();
        $this->assertNotSame($firstRequest->id, $secondRequest->id);
        $this->assertSame('rejected', $firstRequest->fresh()->status);

        $shift->refresh();
        $this->assertEquals($secondRequest->id, $shift->current_request_id);
        $this->assertTrue((bool) $shift->in_workflow);
        $this->assertTrue($secondRequest->requestedShifts()->where('shift_id', $shift->id)->exists());
    }

    #[Test]
    public function rejection_notification_tells_the_requester_about_the_rollback(): void
    {
        $requester = User::factory()->create(['language' => 'de']);
        $this->actingAsAdmin();
        $request = ShiftPlanRequest::create([
            'craft_id' => Craft::factory()->create()->id,
            'week_number' => 19,
            'year' => 2026,
            'status' => 'pending',
            'requested_by_user_id' => $requester->id,
            'requested_at' => now(),
        ]);

        $this->post(route('shift-plan-requests.reject', $request), [])->assertRedirect();

        $expected = __('notification.shift.commit_request_rejected_rollback', [], $requester->language);
        Notification::assertSentTo(
            $requester,
            ShiftNotification::class,
            fn (ShiftNotification $notification) => collect($notification->toArray()->description)
                ->contains(fn ($row) => ($row['title'] ?? null) === $expected)
        );
    }
}
