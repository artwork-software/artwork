<?php

namespace Tests\Feature\Api;

use Artwork\Core\Console\Commands\PurgeOauthTokensCommand;
use Artwork\Modules\User\Models\User;
use Laravel\Passport\ClientRepository;
use Laravel\Passport\Passport;
use Laravel\Passport\Token;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

/**
 * Nächtliches Aufräumen der OAuth-Tokens: wie passport:purge, aber verlängerte Tokens
 * (passport.extended_tokens) bleiben bis zu ihrer Deadline samt Aufbewahrungsfrist stehen.
 */
final class PurgeOauthTokensCommandTest extends FeatureTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        if (!file_exists(Passport::keyPath('oauth-private.key'))) {
            $this->artisan('passport:keys');
        }

        app(ClientRepository::class)->createPersonalAccessGrantClient(
            'Personal Access Client',
            config('auth.guards.api.provider')
        );
    }

    #[Test]
    public function expired_and_revoked_tokens_are_purged_and_valid_ones_stay(): void
    {
        $valid = $this->token();
        $expired = $this->token(['expires_at' => now()->subDays(10)]);
        $recentlyExpired = $this->token(['expires_at' => now()->subDays(2)]);
        $revoked = $this->token(['revoked' => true]);

        $this->artisan(PurgeOauthTokensCommand::class)->assertSuccessful();

        $this->assertTokenExists($valid);
        $this->assertTokenExists($recentlyExpired);
        $this->assertTokenMissing($expired);
        $this->assertTokenMissing($revoked);
    }

    #[Test]
    public function extended_tokens_stay_until_their_deadline_has_passed(): void
    {
        $running = $this->token(['expires_at' => now()->subDays(30)]);
        $over = $this->token(['expires_at' => now()->subDays(60)]);
        $revoked = $this->token(['expires_at' => now()->subDays(30), 'revoked' => true]);
        config()->set('passport.extended_tokens', [
            $running->id => now()->addMonths(3)->toDateTimeString(),
            $over->id => now()->subDays(10)->toDateTimeString(),
            $revoked->id => now()->addMonths(3)->toDateTimeString(),
        ]);

        $this->artisan(PurgeOauthTokensCommand::class)->assertSuccessful();

        $this->assertTokenExists($running);
        $this->assertTokenMissing($over);
        $this->assertTokenMissing($revoked);
    }

    /**
     * @param array<string, mixed> $attributes
     */
    private function token(array $attributes = []): Token
    {
        $token = User::factory()->create()->createToken('Schnittstelle', ['calendar:read'])->token;
        $token->forceFill($attributes)->save();

        return $token;
    }

    private function assertTokenExists(Token $token): void
    {
        $this->assertDatabaseHas('oauth_access_tokens', ['id' => $token->id]);
    }

    private function assertTokenMissing(Token $token): void
    {
        $this->assertDatabaseMissing('oauth_access_tokens', ['id' => $token->id]);
    }
}
