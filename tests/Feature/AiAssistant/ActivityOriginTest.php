<?php

namespace Tests\Feature\AiAssistant;

use Artwork\Modules\AiAssistant\Listeners\AttachActivityOrigin;
use Artwork\Modules\AiAssistant\Settings\AiAssistantSettings;
use Artwork\Modules\User\Models\User;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Laravel\Passport\ClientRepository;
use Laravel\Passport\Passport;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Activitylog\Models\Activity;
use Tests\TestCase;

/**
 * core-via-activity-log: Herkunft jedes Activity-Log-Eintrags (Konzept C1 §6).
 */
final class ActivityOriginTest extends TestCase
{
    private string $aiClientId;

    protected function setUp(): void
    {
        parent::setUp();

        app(ClientRepository::class)
            ->createPersonalAccessGrantClient('PAT client', config('auth.guards.api.provider'));

        $aiClient = Passport::client()->newQuery()->forceCreate([
            'name' => 'ai-session',
            'secret' => null,
            'provider' => config('auth.guards.api.provider'),
            'redirect_uris' => [],
            'grant_types' => ['ai_session'],
            'revoked' => false,
        ]);
        $this->aiClientId = (string) $aiClient->getKey();

        AiAssistantSettings::fake([
            'enabled' => true,
            'level_b_enabled' => false,
            'level_c_enabled' => false,
            'transcript_retention_days' => 0,
            'ai_session_client_id' => $this->aiClientId,
        ]);

        // Eine Route, die einen Log-Eintrag schreibt — der Listener haengt am Request, nicht am Modell.
        Route::middleware('web')->get('/test-only/log-ui', function () {
            activity()->causedBy(auth()->user())->withProperties(['bestand' => 'bleibt'])->log('probe');
            return ['ok' => true];
        });
        Route::middleware(['auth:api'])->get('/test-only/log-api', function () {
            activity()->causedBy(auth('api')->user())->log('probe');
            return ['ok' => true];
        });
    }

    private function lastActivity(): Activity
    {
        return Activity::query()->where('description', 'probe')->latest('id')->firstOrFail();
    }

    #[Test]
    public function a_web_request_is_marked_as_ui(): void
    {
        $this->actingAs(User::factory()->create());

        $this->getJson('/test-only/log-ui')->assertOk();

        $activity = $this->lastActivity();
        $this->assertSame(AttachActivityOrigin::VIA_UI, $activity->properties['via']);
        $this->assertNull($activity->properties['via_detail']);
    }

    #[Test]
    public function existing_properties_survive(): void
    {
        $this->actingAs(User::factory()->create());

        $this->getJson('/test-only/log-ui')->assertOk();

        $this->assertSame('bleibt', $this->lastActivity()->properties['bestand']);
    }

    #[Test]
    public function a_personal_access_token_is_marked_as_api_with_its_name(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('Ticketshop', ['inventory:read']);

        $this->withHeader('Authorization', 'Bearer ' . $token->accessToken)
            ->getJson('/test-only/log-api')
            ->assertOk();

        $activity = $this->lastActivity();
        $this->assertSame(AttachActivityOrigin::VIA_API, $activity->properties['via']);
        $this->assertSame('Ticketshop', $activity->properties['via_detail']);
    }

    #[Test]
    public function an_ai_session_token_is_marked_as_ai_assistant_with_the_conversation_id(): void
    {
        $user = User::factory()->create();
        $conversationId = (string) Str::uuid();

        $activity = $this->activityViaAiToken($user, $conversationId);

        $this->assertSame(AttachActivityOrigin::VIA_AI_ASSISTANT, $activity['via']);
        $this->assertSame($conversationId, $activity['via_detail']);
    }

    #[Test]
    public function an_invalid_conversation_header_yields_a_null_detail(): void
    {
        $user = User::factory()->create();

        $activity = $this->activityViaAiToken($user, 'kein-uuid');

        $this->assertSame(AttachActivityOrigin::VIA_AI_ASSISTANT, $activity['via']);
        $this->assertNull($activity['via_detail']);
    }

    /**
     * Der Listener liest die Client-ID aus AccessToken::oauth_client_id. Fuer den Test wird genau
     * dieser Zustand hergestellt, statt den Grant nachzubauen.
     *
     * @return array<string, mixed>
     */
    private function activityViaAiToken(User $user, string $conversationHeader): array
    {
        $request = \Illuminate\Http\Request::create('/irgendwo', 'GET');
        $request->headers->set('Authorization', 'Bearer irrelevant');
        $request->headers->set(AttachActivityOrigin::CONVERSATION_HEADER, $conversationHeader);

        $user->withAccessToken(new \Laravel\Passport\AccessToken([
            'oauth_access_token_id' => 'x',
            'oauth_client_id' => $this->aiClientId,
            'oauth_user_id' => (string) $user->id,
            'oauth_scopes' => ['ai:transcript'],
        ]));

        $this->app->instance('request', $request);
        $this->app['auth']->guard('api')->setUser($user);

        activity()->causedBy($user)->log('probe');

        return $this->lastActivity()->properties->toArray();
    }

    #[Test]
    public function a_console_run_is_marked_as_system(): void
    {
        $this->artisan('ai:purge-transcripts')->assertSuccessful();

        activity()->log('probe');

        $this->assertSame(AttachActivityOrigin::VIA_SYSTEM, $this->lastActivity()->properties['via']);
    }
}
