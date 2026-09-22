<?php

namespace Tests\Unit\Modules\ExternalUserManagement;

use Artwork\Modules\ExternalUserManagement\Mail\ExternalUserImported;
use Artwork\Modules\ExternalUserManagement\Models\ExternalUser;
use Artwork\Modules\ExternalUserManagement\Models\ExternalUserSource;
use Artwork\Modules\User\Models\User;
use PHPUnit\Framework\Attributes\Test;
use RuntimeException;
use Symfony\Component\Mailer\Exception\TransportException;
use Symfony\Component\Mailer\Exception\UnexpectedResponseException;
use Tests\TestCase;

final class ExternalUserImportedMailTest extends TestCase
{
    #[Test]
    public function a_permanent_smtp_rejection_keeps_the_sent_flag_so_the_sync_does_not_retry_forever(): void
    {
        $externalUser = $this->externalUser();

        (new ExternalUserImported($externalUser->user, $externalUser))
            ->failed(new UnexpectedResponseException(
                'Expected response code "250" but got code "550", with message "550 5.7.1 Message rejected".',
                550
            ));

        $this->assertNotNull($externalUser->fresh()->import_notification_sent_at);
    }

    #[Test]
    public function a_transient_transport_failure_resets_the_sent_flag_for_the_next_run(): void
    {
        $externalUser = $this->externalUser();

        (new ExternalUserImported($externalUser->user, $externalUser))
            ->failed(new TransportException('Connection could not be established with host smtp:25', 0));

        $this->assertNull($externalUser->fresh()->import_notification_sent_at);
    }

    #[Test]
    public function a_non_transport_failure_resets_the_sent_flag_for_the_next_run(): void
    {
        $externalUser = $this->externalUser();

        (new ExternalUserImported($externalUser->user, $externalUser))
            ->failed(new RuntimeException('view not found'));

        $this->assertNull($externalUser->fresh()->import_notification_sent_at);
    }

    private function externalUser(): ExternalUser
    {
        $source = ExternalUserSource::query()->create([
            'name' => 'IdP',
            'active' => true,
            'type' => 'identity_provider',
            'config' => [
                'discovery_url' => 'https://idp.example.com/.well-known/openid-configuration',
                'client_id' => 'artwork',
                'client_secret' => 'secret',
            ],
        ]);
        $user = User::factory()->create(['ad_managed' => true, 'ad_identifier' => 'ext-1']);

        return ExternalUser::query()->create([
            'source_id' => $source->id,
            'user_id' => $user->id,
            'identification' => 'ext-1',
            'import_notification_sent_at' => now(),
        ]);
    }
}
