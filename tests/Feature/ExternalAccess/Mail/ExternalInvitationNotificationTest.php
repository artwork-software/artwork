<?php

namespace Tests\Feature\ExternalAccess\Mail;

use Artwork\Modules\ExternalAccess\Notifications\ExternalInvitationNotification;
use Artwork\Modules\ExternalAccess\Notifications\ExternalLoginLinkNotification;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\User\Models\User;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class ExternalInvitationNotificationTest extends TestCase
{
    #[Test]
    public function invitation_mail_contains_inviter_name_and_token_url(): void
    {
        $inviter = User::factory()->create(['first_name' => 'Grace', 'last_name' => 'Hopper']);
        $token = Str::random(64);

        $mail = (new ExternalInvitationNotification($token, $inviter))->toMail(null);
        $rendered = (string) $mail->render();

        $this->assertStringContainsString('Grace Hopper', $rendered);
        $this->assertStringContainsString(route('external.login.redeem', ['token' => $token]), $rendered);
    }

    #[Test]
    public function invitation_mail_includes_project_name_when_invited_from_project(): void
    {
        $inviter = User::factory()->create();
        $project = Project::factory()->create(['name' => 'Sommerfestival 2026']);

        $mail = (new ExternalInvitationNotification(Str::random(64), $inviter, $project))->toMail(null);
        $rendered = (string) $mail->render();

        $this->assertStringContainsString('Sommerfestival 2026', $rendered);
    }

    #[Test]
    public function invitation_mail_omits_project_section_for_crm_only_invite(): void
    {
        $inviter = User::factory()->create();

        $mail = (new ExternalInvitationNotification(Str::random(64), $inviter, null))->toMail(null);
        $rendered = (string) $mail->render();

        $this->assertStringNotContainsString('gain access to selected information', $rendered);
    }

    #[Test]
    public function subsequent_login_uses_the_plain_login_notification(): void
    {
        $token = Str::random(64);

        $mail = (new ExternalLoginLinkNotification($token))->toMail(null);
        $rendered = (string) $mail->render();

        $this->assertStringContainsString(route('external.login.redeem', ['token' => $token]), $rendered);
    }
}
