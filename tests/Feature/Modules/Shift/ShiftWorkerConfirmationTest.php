<?php

namespace Tests\Feature\Modules\Shift;

use App\Settings\ShiftSettings;
use Artwork\Modules\Freelancer\Models\Freelancer;
use Artwork\Modules\Shift\Models\Shift;
use Artwork\Modules\Shift\Models\ShiftQualification;
use Artwork\Modules\Shift\Models\ShiftWorker;
use Artwork\Modules\User\Models\User;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Activitylog\Models\Activity;
use Tests\Feature\FeatureTestCase;

/**
 * Zu-/Absage einer Schichtzuweisung durch die eingeplante Person
 * (bzw. Proxy-Erfassung durch Planer:innen für Externe).
 */
final class ShiftWorkerConfirmationTest extends FeatureTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $settings = app(ShiftSettings::class);
        $settings->shift_confirmation_enabled = true;
        $settings->save();
    }

    private function createCommittedShiftWithUser(bool $committed = true): array
    {
        $shift = Shift::factory()->create([
            'start_date' => '2026-08-10',
            'end_date' => '2026-08-10',
            'start' => '10:00',
            'end' => '18:00',
            'is_committed' => $committed,
            'committing_user_id' => $committed ? User::factory()->create()->id : null,
        ]);
        $qualification = ShiftQualification::factory()->create();
        $user = User::factory()->create();
        $shift->users()->attach($user->id, [
            'shift_qualification_id' => $qualification->id,
        ]);

        $pivot = ShiftWorker::query()
            ->where('shift_id', $shift->id)
            ->where('employable_type', User::class)
            ->where('employable_id', $user->id)
            ->firstOrFail();

        return [$shift->fresh(), $user, $pivot];
    }

    #[Test]
    public function worker_can_accept_own_committed_shift(): void
    {
        [, $user, $pivot] = $this->createCommittedShiftWithUser();

        $this->actingAs($user)
            ->patch(route('shift-worker.confirmation.update', $pivot), [
                'status' => 'accepted',
            ])
            ->assertRedirect();

        $pivot->refresh();
        $this->assertSame('accepted', $pivot->confirmation_status);
        $this->assertNotNull($pivot->confirmation_at);
        $this->assertSame($user->id, $pivot->confirmation_by_user_id);
        $this->assertNull($pivot->confirmation_comment);
    }

    #[Test]
    public function worker_can_decline_with_comment_and_switch_to_accept_clears_comment(): void
    {
        [, $user, $pivot] = $this->createCommittedShiftWithUser();

        $this->actingAs($user)
            ->patch(route('shift-worker.confirmation.update', $pivot), [
                'status' => 'declined',
                'comment' => 'Bin an dem Tag verhindert',
            ])
            ->assertRedirect();

        $pivot->refresh();
        $this->assertSame('declined', $pivot->confirmation_status);
        $this->assertSame('Bin an dem Tag verhindert', $pivot->confirmation_comment);

        // Antwort ist jederzeit änderbar; alter Ablehngrund darf nicht kleben bleiben
        $this->actingAs($user)
            ->patch(route('shift-worker.confirmation.update', $pivot), [
                'status' => 'accepted',
            ])
            ->assertRedirect();

        $pivot->refresh();
        $this->assertSame('accepted', $pivot->confirmation_status);
        $this->assertNull($pivot->confirmation_comment);
    }

    #[Test]
    public function confirmation_is_rejected_when_feature_disabled(): void
    {
        [, $user, $pivot] = $this->createCommittedShiftWithUser();

        $settings = app(ShiftSettings::class);
        $settings->shift_confirmation_enabled = false;
        $settings->save();

        $this->actingAs($user)
            ->patch(route('shift-worker.confirmation.update', $pivot), [
                'status' => 'accepted',
            ])
            ->assertForbidden();
    }

    #[Test]
    public function confirmation_is_rejected_on_uncommitted_shift(): void
    {
        [, $user, $pivot] = $this->createCommittedShiftWithUser(committed: false);

        $this->actingAs($user)
            ->patch(route('shift-worker.confirmation.update', $pivot), [
                'status' => 'accepted',
            ])
            ->assertForbidden();
    }

    #[Test]
    public function other_user_without_planner_permission_cannot_respond(): void
    {
        [, , $pivot] = $this->createCommittedShiftWithUser();

        $this->actingAs(User::factory()->create())
            ->patch(route('shift-worker.confirmation.update', $pivot), [
                'status' => 'accepted',
            ])
            ->assertForbidden();
    }

    #[Test]
    public function planner_can_record_response_for_freelancer(): void
    {
        $shift = Shift::factory()->create([
            'start_date' => '2026-08-10',
            'end_date' => '2026-08-10',
            'start' => '10:00',
            'end' => '18:00',
            'is_committed' => true,
            'committing_user_id' => User::factory()->create()->id,
        ]);
        $qualification = ShiftQualification::factory()->create();
        $freelancer = Freelancer::factory()->create();
        $shift->freelancer()->attach($freelancer->id, [
            'shift_qualification_id' => $qualification->id,
        ]);

        $pivot = ShiftWorker::query()
            ->where('shift_id', $shift->id)
            ->where('employable_type', Freelancer::class)
            ->where('employable_id', $freelancer->id)
            ->firstOrFail();

        $planner = $this->actingAsUserWith('can plan shifts');

        $this->patch(route('shift-worker.confirmation.update', $pivot), [
            'status' => 'declined',
            'comment' => 'Telefonisch abgesagt',
        ])->assertRedirect();

        $pivot->refresh();
        $this->assertSame('declined', $pivot->confirmation_status);
        $this->assertSame($planner->id, $pivot->confirmation_by_user_id);
    }

    #[Test]
    public function assigner_is_notified_when_worker_responds(): void
    {
        [, $user, $pivot] = $this->createCommittedShiftWithUser();
        $assigner = User::factory()->create();
        $pivot->update(['assigned_by_user_id' => $assigner->id]);

        $this->actingAs($user)
            ->patch(route('shift-worker.confirmation.update', $pivot), [
                'status' => 'accepted',
            ])
            ->assertRedirect();

        \Illuminate\Support\Facades\Notification::assertSentTo(
            $assigner,
            \Artwork\Modules\Shift\Notifications\ShiftNotification::class
        );
    }

    #[Test]
    public function confirmation_writes_shift_activity_log_entry(): void
    {
        [$shift, $user, $pivot] = $this->createCommittedShiftWithUser();

        $this->actingAs($user)
            ->patch(route('shift-worker.confirmation.update', $pivot), [
                'status' => 'accepted',
            ])
            ->assertRedirect();

        $activity = Activity::query()
            ->where('log_name', 'shift')
            ->where('subject_type', Shift::class)
            ->where('subject_id', $shift->id)
            ->where('event', 'confirmation_accepted')
            ->first();

        $this->assertNotNull($activity);
        $this->assertSame(
            '{0} accepted their shift assignment for {1} ({2})',
            $activity->properties['translation_key']
        );
    }

    #[Test]
    public function changing_shift_times_resets_confirmations(): void
    {
        [$shift, $user, $pivot] = $this->createCommittedShiftWithUser();

        $this->actingAs($user)
            ->patch(route('shift-worker.confirmation.update', $pivot), [
                'status' => 'accepted',
            ])
            ->assertRedirect();

        $this->assertSame('accepted', $pivot->refresh()->confirmation_status);

        // Zeitänderung → Antwort bezog sich auf die alte Zeit → zurück auf "ausstehend"
        $shift->update(['start' => '12:00']);

        $pivot->refresh();
        $this->assertNull($pivot->confirmation_status);
        $this->assertNull($pivot->confirmation_at);
        $this->assertNull($pivot->confirmation_by_user_id);
    }

    #[Test]
    public function unrelated_shift_update_keeps_confirmation(): void
    {
        [$shift, $user, $pivot] = $this->createCommittedShiftWithUser();

        $this->actingAs($user)
            ->patch(route('shift-worker.confirmation.update', $pivot), [
                'status' => 'accepted',
            ])
            ->assertRedirect();

        $shift->update(['description' => 'Neue Beschreibung']);

        $this->assertSame('accepted', $pivot->refresh()->confirmation_status);
    }
}
