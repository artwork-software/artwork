<?php

namespace Tests\Feature\ExternalAccess\Management;

use Artwork\Modules\ExternalAccess\Exceptions\AccessNotActiveException;
use Artwork\Modules\ExternalAccess\Models\ExternalAccess;
use Artwork\Modules\ExternalAccess\Notifications\ExternalInvitationNotification;
use Artwork\Modules\ExternalAccess\Services\ExternalAccessService;
use Artwork\Modules\User\Models\User;
use Illuminate\Support\Facades\Notification;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Activitylog\Models\Activity;
use Tests\Feature\ExternalAccess\ExternalAccessTestCase as TestCase;

final class ResendInvitationTest extends TestCase
{
    #[Test]
    public function resend_creates_invitation_row_and_sends_mail(): void
    {
        Notification::fake();
        $actor = User::factory()->create();
        $external = ExternalAccess::factory()->active()->create();

        $invitation = app(ExternalAccessService::class)->resendInvitation($external, $actor);

        $this->assertSame($actor->id, (int) $invitation->invited_by_user_id);
        $this->assertSame(1, $external->loginTokens()->count());
        Notification::assertSentOnDemand(ExternalInvitationNotification::class);
        $this->assertTrue(
            Activity::query()->where('log_name', 'external_access_management')
                ->where('description', 'invitation_resent')->exists()
        );
    }

    #[Test]
    public function resend_is_refused_without_active_access(): void
    {
        Notification::fake();
        $external = ExternalAccess::factory()->crmAccessExpired()->create();

        $this->expectException(AccessNotActiveException::class);

        app(ExternalAccessService::class)->resendInvitation($external, User::factory()->create());
    }

    #[Test]
    public function inviter_can_resend_via_management_route(): void
    {
        Notification::fake();
        $inviter = User::factory()->create();
        $external = ExternalAccess::factory()->active()->create(['invited_by_user_id' => $inviter->id]);
        $this->actingAs($inviter);

        $this->post(route('crm.external-access.resend-invitation', $external->id))
            ->assertRedirect()
            ->assertSessionHas('status');
    }
}
