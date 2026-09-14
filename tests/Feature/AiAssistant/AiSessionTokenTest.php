<?php

namespace Tests\Feature\AiAssistant;

use Artwork\Modules\AiAssistant\Settings\AiAssistantSettings;
use Artwork\Modules\AiAssistant\Support\AiScopes;
use Artwork\Modules\User\Models\User;
use Illuminate\Auth\Events\Logout;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Route;
use Laravel\Passport\Client;
use Laravel\Passport\ClientRepository;
use Laravel\Passport\Http\Middleware\CheckToken;
use Laravel\Passport\Passport;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class AiSessionTokenTest extends TestCase
{
    private Client $aiClient;

    protected function setUp(): void
    {
        parent::setUp();

        $this->aiClient = Passport::client()->newQuery()->forceCreate([
            'name' => 'ai-session',
            'secret' => null,
            'provider' => config('auth.guards.api.provider'),
            'redirect_uris' => [],
            'grant_types' => ['ai_session'],
            'revoked' => false,
        ]);

        $this->house(enabled: true);
    }

    private function house(bool $enabled, bool $levelB = false, bool $levelC = false): void
    {
        config(['ai.enabled' => true]);

        AiAssistantSettings::fake([
            'enabled' => $enabled,
            'level_b_enabled' => $levelB,
            'level_c_enabled' => $levelC,
            'transcript_retention_days' => 0,
            'ai_session_client_id' => (string) $this->aiClient->getKey(),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function claims(string $jwt): array
    {
        $payload = explode('.', $jwt)[1];

        return json_decode(base64_decode(strtr($payload, '-_', '+/')), true);
    }

    // --- Freischaltung -----------------------------------------------------------------

    #[Test]
    public function a_disabled_house_yields_403(): void
    {
        $this->actingAs(User::factory()->create());
        $this->house(enabled: false);

        $this->postJson(route('ai.session.store'))->assertForbidden();
    }

    /** Gate::before darf hier nicht greifen — deshalb ist die Regel ein Service, kein Gate. */
    #[Test]
    public function a_disabled_house_yields_403_even_for_an_admin(): void
    {
        $this->actingAs($this->adminUser());
        $this->house(enabled: false);

        $this->postJson(route('ai.session.store'))->assertForbidden();
    }

    #[Test]
    public function the_instance_kill_switch_yields_403(): void
    {
        $this->actingAs($this->adminUser());
        $this->house(enabled: true);
        config(['ai.enabled' => false]);

        $this->postJson(route('ai.session.store'))->assertForbidden();
    }

    // --- Befund A ----------------------------------------------------------------------

    /**
     * Der Grant darf nicht am Singleton-AuthorizationServer haengen, sonst koennte sich jeder
     * ueber den oeffentlichen Token-Endpunkt ein Token fuer einen fremden User ausstellen.
     */
    #[Test]
    public function the_public_token_endpoint_does_not_know_the_ai_session_grant(): void
    {
        $victim = User::factory()->create();

        $response = $this->postJson('/oauth/token', [
            'grant_type' => 'ai_session',
            'user_id' => $victim->id,
            'provider' => config('auth.guards.api.provider'),
            'scope' => AiScopes::TRANSCRIPT,
        ]);

        $response->assertStatus(400);
        $this->assertSame('unsupported_grant_type', $response->json('error'));
    }

    // --- Scope-Schnitt -----------------------------------------------------------------

    /**
     * @return array<string, array{0: bool, 1: bool, 2: string[]}>
     */
    public static function stageCombinations(): array
    {
        return [
            'keine Stufe' => [false, false, [AiScopes::TRANSCRIPT]],
            'nur B' => [true, false, [AiScopes::TRANSCRIPT, AiScopes::READ]],
            'nur C' => [false, true, [AiScopes::TRANSCRIPT]],
            'B und C' => [true, true, [AiScopes::TRANSCRIPT, AiScopes::READ]],
        ];
    }

    /**
     * @param string[] $expected
     */
    #[Test]
    #[\PHPUnit\Framework\Attributes\DataProvider('stageCombinations')]
    public function the_scope_set_follows_the_house_settings(bool $levelB, bool $levelC, array $expected): void
    {
        $this->actingAs(User::factory()->create());
        $this->house(enabled: true, levelB: $levelB, levelC: $levelC);

        $response = $this->postJson(route('ai.session.store'))->assertOk();

        $this->assertSame($expected, $response->json('scopes'));
        $this->assertNotContains(AiScopes::WRITE, $response->json('scopes'));
    }

    #[Test]
    public function a_requested_scope_can_only_narrow_the_set(): void
    {
        $this->actingAs(User::factory()->create());
        $this->house(enabled: true, levelB: true);

        $response = $this->postJson(route('ai.session.store'), [
            'scopes' => [AiScopes::TRANSCRIPT],
        ])->assertOk();

        $this->assertSame([AiScopes::TRANSCRIPT], $response->json('scopes'));
    }

    #[Test]
    public function requesting_the_write_scope_is_rejected(): void
    {
        $this->actingAs(User::factory()->create());
        $this->house(enabled: true, levelB: true, levelC: true);

        $this->postJson(route('ai.session.store'), ['scopes' => [AiScopes::WRITE]])
            ->assertStatus(422)
            ->assertJsonValidationErrors('scopes.0');
    }

    // --- Das Token selbst --------------------------------------------------------------

    #[Test]
    public function the_token_carries_the_expected_claims(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        $this->house(enabled: true, levelB: true);

        $response = $this->postJson(route('ai.session.store'))->assertOk();
        $claims = $this->claims($response->json('access_token'));

        $this->assertSame((string) $user->id, (string) $claims['sub']);
        $this->assertSame([AiScopes::TRANSCRIPT, AiScopes::READ], $claims['scopes']);
        $this->assertEqualsWithDelta(900, $claims['exp'] - $claims['iat'], 5, 'TTL muss 15 Minuten sein');
        $this->assertSame(
            (string) $this->aiClient->getKey(),
            (string) (is_array($claims['aud']) ? $claims['aud'][0] : $claims['aud'])
        );
    }

    #[Test]
    public function the_token_authenticates_against_the_api_guard_and_respects_its_scopes(): void
    {
        Route::middleware(['auth:api', CheckToken::using(AiScopes::TRANSCRIPT)])
            ->get('/test-only/ai-transcript', fn (): array => ['ok' => true]);
        Route::middleware(['auth:api', CheckToken::using('inventory:read')])
            ->get('/test-only/inventory', fn (): array => ['ok' => true]);

        $this->actingAs(User::factory()->create());
        $this->house(enabled: true);

        $token = $this->postJson(route('ai.session.store'))->assertOk()->json('access_token');

        $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/test-only/ai-transcript')
            ->assertOk();

        $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/test-only/inventory')
            ->assertForbidden();
    }

    // --- Lebenszyklus ------------------------------------------------------------------

    #[Test]
    public function logout_revokes_open_session_tokens_but_leaves_personal_access_tokens_alone(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        $this->house(enabled: true);

        $this->postJson(route('ai.session.store'))->assertOk();

        $personalAccessToken = app(ClientRepository::class)
            ->createPersonalAccessGrantClient('PAT client', config('auth.guards.api.provider'));
        $pat = $user->createToken('kunden-key', ['inventory:read']);

        Event::dispatch(new Logout('web', $user));

        $this->assertSame(0, $this->openAiTokens($user), 'Session-Token muss widerrufen sein');
        $this->assertFalse($pat->token->fresh()->revoked, 'Der Kunden-Key darf nicht betroffen sein');
        $this->assertNotNull($personalAccessToken);
    }

    #[Test]
    public function a_password_reset_revokes_open_session_tokens(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        $this->house(enabled: true);

        $this->postJson(route('ai.session.store'))->assertOk();
        $this->assertSame(1, $this->openAiTokens($user));

        Event::dispatch(new PasswordReset($user));

        $this->assertSame(0, $this->openAiTokens($user));
    }

    private function openAiTokens(User $user): int
    {
        return Passport::token()->newQuery()
            ->where('user_id', $user->id)
            ->where('client_id', $this->aiClient->getKey())
            ->where('revoked', false)
            ->count();
    }

    // --- Throttle ----------------------------------------------------------------------

    #[Test]
    public function the_eleventh_request_per_minute_is_throttled(): void
    {
        $this->actingAs(User::factory()->create());
        $this->house(enabled: true);

        for ($i = 0; $i < 10; $i++) {
            $this->postJson(route('ai.session.store'))->assertOk();
        }

        $this->postJson(route('ai.session.store'))->assertStatus(429);
    }
}
