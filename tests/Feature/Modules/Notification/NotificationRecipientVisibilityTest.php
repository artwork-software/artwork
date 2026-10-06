<?php

namespace Tests\Feature\Modules\Notification;

use Artwork\Modules\Craft\Models\Craft;
use Artwork\Modules\Notification\Enums\NotificationEnum;
use Artwork\Modules\Notification\Enums\NotificationGroupEnum;
use Artwork\Modules\Notification\Services\NotificationSettingService;
use Artwork\Modules\Notification\Services\NotificationSettingsPresenter;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\Shift\Models\Shift;
use Artwork\Modules\Shift\Models\ShiftCommitWorkflowUser;
use Artwork\Modules\Shift\Models\ShiftQualification;
use Artwork\Modules\User\Models\User;
use Illuminate\Notifications\ChannelManager;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

/**
 * Wer einen Typ bekommt, muss ihn in den Einstellungen sehen und abschalten können – sonst kommen
 * Mails/Sammelmails, die sich nicht abbestellen lassen, und „Alle E-Mails aus“ überspringt sie.
 * Vorher galten Konflikthinweise (gehen an die eingeplante Person) und offene Bedarfe (gehen an
 * Projektleitungen) als reine Planer-Typen.
 */
final class NotificationRecipientVisibilityTest extends FeatureTestCase
{
    private function userWithSettings(): User
    {
        $user = User::factory()->create();
        app(NotificationSettingService::class)->ensureDefaultsForUser($user);

        return $user;
    }

    /**
     * @return array<int, string>
     */
    private function visibleTypes(User $user): array
    {
        return app(NotificationSettingsPresenter::class)->visibleTypeValuesFor($user);
    }

    private function emailEnabled(User $user, NotificationEnum $type): bool
    {
        return (bool) $user->notificationSettings()->where('type', $type->value)->value('enabled_email');
    }

    private function storeNotification(User $user, NotificationEnum $type): void
    {
        DB::table('notifications')->insert([
            'id' => (string) Str::uuid(),
            'type' => $type->notificationClass() ?? 'test',
            'notifiable_type' => $user->getMorphClass(),
            'notifiable_id' => $user->id,
            'data' => json_encode([
                'type' => $type->value,
                'groupType' => $type->groupType(),
                'title' => 'Test',
                'description' => [],
                'buttons' => [],
            ]),
            'read_at' => null,
            'sent_in_summary' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    #[Test]
    public function scheduled_workers_can_see_and_switch_off_shift_conflicts(): void
    {
        $worker = $this->actingAsUserWith([]);
        app(NotificationSettingService::class)->ensureDefaultsForUser($worker);

        $this->assertContains(NotificationEnum::NOTIFICATION_SHIFT_CONFLICT->value, $this->visibleTypes($worker));
        // reine Planer-Hinweise bleiben verborgen
        $this->assertNotContains(
            NotificationEnum::NOTIFICATION_SHIFT_INFRINGEMENT->value,
            $this->visibleTypes($worker)
        );

        $this->patchJson(route('notifications.settings.bulk'), ['enabled_email' => false])->assertOk();

        $this->assertFalse($this->emailEnabled($worker, NotificationEnum::NOTIFICATION_SHIFT_CONFLICT));
    }

    #[Test]
    public function open_demand_recipients_see_the_type_before_the_first_notification_and_can_switch_it_off(): void
    {
        Notification::swap(new ChannelManager($this->app));
        $craft = Craft::factory()->create(['notify_days' => 2, 'assignable_by_all' => false]);
        $project = Project::factory()->create();
        $manager = $this->userWithSettings();
        $planner = $this->userWithSettings();
        $worker = $this->userWithSettings();
        $project->users()->attach($manager->id, ['is_manager' => true]);
        $craft->craftShiftPlaner()->attach($planner->id);
        $worker->assignedCrafts()->attach($craft->id);

        // vor der ersten Meldung: Empfängerkreis aus Rollen, nicht aus vorhandenen Meldungen
        $openDemand = NotificationEnum::NOTIFICATION_SHIFT_OPEN_DEMAND->value;
        $this->assertContains($openDemand, $this->visibleTypes($manager));
        $this->assertContains($openDemand, $this->visibleTypes($planner));
        $this->assertNotContains($openDemand, $this->visibleTypes($worker));

        $shift = Shift::factory()->create([
            'craft_id' => $craft->id,
            'event_id' => null,
            'project_id' => $project->id,
            'start_date' => now()->addDays(2)->toDateString(),
            'end_date' => now()->addDays(2)->toDateString(),
        ]);
        $shift->shiftsQualifications()->create([
            'shift_qualification_id' => ShiftQualification::factory()->create()->id,
            'value' => 2,
        ]);
        $this->artisan('artwork:notify-craft-if-shift-deadline-reached')->assertSuccessful();

        foreach ([$manager, $planner] as $recipient) {
            $receivedTypes = $recipient->notifications()->get()->map(fn ($notification) => $notification->data['type']);
            $this->assertContains($openDemand, $receivedTypes->all());
            foreach ($receivedTypes->unique() as $receivedType) {
                $this->assertContains($receivedType, $this->visibleTypes($recipient));
            }

            $this->actingAs($recipient);
            $this->patchJson(route('notifications.settings.bulk'), [
                'groupType' => NotificationGroupEnum::SHIFTS->value,
                'enabled_email' => false,
            ])->assertOk();
            $this->assertFalse($this->emailEnabled($recipient, NotificationEnum::NOTIFICATION_SHIFT_OPEN_DEMAND));
        }
    }

    #[Test]
    public function commit_workflow_approvers_and_craft_managers_see_commit_requests(): void
    {
        $commitRequest = NotificationEnum::NOTIFICATION_NEW_SHIFT_COMMIT_WORKFLOW_REQUEST->value;
        $approver = $this->userWithSettings();
        ShiftCommitWorkflowUser::factory()->create(['user_id' => $approver->id]);
        $craftManager = $this->userWithSettings();
        Craft::factory()->create()->managingUsers()->attach($craftManager->id);
        $nobody = $this->userWithSettings();

        $this->assertContains($commitRequest, $this->visibleTypes($approver));
        $this->assertContains($commitRequest, $this->visibleTypes($craftManager));
        $this->assertNotContains($commitRequest, $this->visibleTypes($nobody));
        $this->assertNotContains(
            NotificationEnum::NOTIFICATION_SHIFT_WORKER_CONFIRMATION->value,
            $this->visibleTypes($approver)
        );
    }

    #[Test]
    public function every_type_a_person_has_received_is_visible_and_switchable(): void
    {
        // Admins sehen alle Typen, die in dieser Instanz anfallen können
        $allAvailable = $this->visibleTypes($this->adminUser());
        $recipient = $this->userWithSettings();
        foreach ($allAvailable as $typeValue) {
            $this->storeNotification($recipient, NotificationEnum::from($typeValue));
        }

        $this->assertEqualsCanonicalizing($allAvailable, $this->visibleTypes($recipient));

        $this->actingAs($recipient);
        $this->patchJson(route('notifications.settings.bulk'), ['enabled_email' => false])->assertOk();
        $this->assertSame(
            0,
            $recipient->notificationSettings()->whereIn('type', $allAvailable)->where('enabled_email', true)->count()
        );
    }
}
