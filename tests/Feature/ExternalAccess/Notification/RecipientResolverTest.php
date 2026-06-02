<?php

namespace Tests\Feature\ExternalAccess\Notification;

use Artwork\Modules\Department\Models\Department;
use Artwork\Modules\ExternalAccess\Models\ExternalAccess;
use Artwork\Modules\ExternalAccess\Models\ExternalAccessNotificationRecipient;
use Artwork\Modules\ExternalAccess\Services\ExternalNotificationRecipientResolver;
use Artwork\Modules\Notification\Enums\NotificationEnum;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\User\Models\User;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class RecipientResolverTest extends TestCase
{
    private function resolver(): ExternalNotificationRecipientResolver
    {
        return app(ExternalNotificationRecipientResolver::class);
    }

    private function blanket(object $recipient, NotificationEnum $type): void
    {
        ExternalAccessNotificationRecipient::create([
            'recipient_type' => $recipient->getMorphClass(),
            'recipient_id' => $recipient->id,
            'notification_types' => [$type->value],
        ]);
    }

    #[Test]
    public function crm_submission_includes_inviter(): void
    {
        $inviter = User::factory()->create();
        $external = ExternalAccess::factory()->create(['invited_by_user_id' => $inviter->id]);

        $recipients = $this->resolver()->resolveForCrmSubmission($external);

        $this->assertTrue($recipients->contains(fn ($u) => $u->id === $inviter->id));
    }

    #[Test]
    public function crm_submission_handles_deleted_inviter_gracefully(): void
    {
        $external = ExternalAccess::factory()->create(['invited_by_user_id' => null]);

        $recipients = $this->resolver()->resolveForCrmSubmission($external);

        $this->assertCount(0, $recipients);
    }

    #[Test]
    public function crm_submission_includes_blanket_user_recipient(): void
    {
        $external = ExternalAccess::factory()->create(['invited_by_user_id' => null]);
        $blanketUser = User::factory()->create();
        $this->blanket($blanketUser, NotificationEnum::NOTIFICATION_EXTERNAL_CRM_SUBMITTED);

        $recipients = $this->resolver()->resolveForCrmSubmission($external);

        $this->assertTrue($recipients->contains(fn ($u) => $u->id === $blanketUser->id));
    }

    #[Test]
    public function tab_update_includes_inviter_and_managers(): void
    {
        $inviter = User::factory()->create();
        $manager = User::factory()->create();
        $external = ExternalAccess::factory()->create(['invited_by_user_id' => $inviter->id]);
        $project = Project::factory()->create();
        $project->users()->attach($manager->id, ['is_manager' => true]);

        $recipients = $this->resolver()->resolveForTabComponentUpdate($external, $project);

        $ids = $recipients->pluck('id')->all();
        $this->assertContains($inviter->id, $ids);
        $this->assertContains($manager->id, $ids);
    }

    #[Test]
    public function tab_update_deduplicates_when_inviter_is_also_manager(): void
    {
        $person = User::factory()->create();
        $external = ExternalAccess::factory()->create(['invited_by_user_id' => $person->id]);
        $project = Project::factory()->create();
        $project->users()->attach($person->id, ['is_manager' => true]);

        $recipients = $this->resolver()->resolveForTabComponentUpdate($external, $project);

        $this->assertSame(1, $recipients->where('id', $person->id)->count());
    }

    #[Test]
    public function tab_update_resolves_department_recipients_to_users(): void
    {
        $external = ExternalAccess::factory()->create(['invited_by_user_id' => null]);
        $project = Project::factory()->create();
        $deptUser = User::factory()->create();
        $department = Department::factory()->create();
        $department->users()->attach($deptUser->id);
        $this->blanket($department, NotificationEnum::NOTIFICATION_EXTERNAL_TAB_COMPONENT_UPDATED);

        $recipients = $this->resolver()->resolveForTabComponentUpdate($external, $project);

        $this->assertTrue($recipients->contains(fn ($u) => $u->id === $deptUser->id));
    }

    #[Test]
    public function blanket_recipients_filtered_by_notification_type(): void
    {
        $external = ExternalAccess::factory()->create(['invited_by_user_id' => null]);
        $crmOnlyUser = User::factory()->create();
        $this->blanket($crmOnlyUser, NotificationEnum::NOTIFICATION_EXTERNAL_CRM_SUBMITTED);

        // A tab-update resolution must NOT include a recipient configured only for CRM.
        $project = Project::factory()->create();
        $recipients = $this->resolver()->resolveForTabComponentUpdate($external, $project);

        $this->assertFalse($recipients->contains(fn ($u) => $u->id === $crmOnlyUser->id));
    }
}
