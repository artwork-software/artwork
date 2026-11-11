<?php

namespace Tests\Feature\AiAssistant;

use Artwork\Modules\AiAssistant\Settings\AiAssistantSettings;
use Laravel\Passport\Passport;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class AiInstallCommandTest extends TestCase
{
    private function aiClients(): \Illuminate\Database\Eloquent\Collection
    {
        return Passport::client()->newQuery()->where('name', 'ai-session')->get();
    }

    #[Test]
    public function it_creates_the_client_and_stores_its_id(): void
    {
        $this->artisan('ai:install')->assertSuccessful();

        $clients = $this->aiClients();
        $this->assertCount(1, $clients);

        $client = $clients->first();
        $this->assertSame(['ai_session'], $client->grant_types);
        $this->assertSame(
            (string) $client->getKey(),
            app(AiAssistantSettings::class)->ai_session_client_id
        );
    }

    /**
     * Ein Secret wuerde den Client confidential machen. Bei Integer-Client-IDs traegt ein
     * confidential First-Party-Client client_credentials, und TokenGuard.php:146 sperrt dann den
     * User aus, dessen ID mit der Client-ID uebereinstimmt.
     */
    #[Test]
    public function the_client_has_no_secret(): void
    {
        $this->artisan('ai:install')->assertSuccessful();

        $client = $this->aiClients()->first();

        $this->assertNull($client->getAttributes()['secret']);
        $this->assertFalse($client->confidential());
        $this->assertFalse($client->hasGrantType('client_credentials'));
    }

    #[Test]
    public function running_it_twice_creates_only_one_client(): void
    {
        $this->artisan('ai:install')->assertSuccessful();
        $firstId = app(AiAssistantSettings::class)->ai_session_client_id;

        $this->artisan('ai:install')->assertSuccessful();

        $this->assertCount(1, $this->aiClients());
        $this->assertSame($firstId, app(AiAssistantSettings::class)->ai_session_client_id);
    }
}
