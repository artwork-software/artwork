<?php

namespace Tests\Feature\AiAssistant;

use Artwork\Modules\AiAssistant\Settings\AiAssistantSettings;
use Artwork\Modules\AiAssistant\Support\AiScopes;
use Artwork\Modules\Permission\Enums\PermissionEnum;
use Artwork\Modules\User\Models\User;
use Inertia\Testing\AssertableInertia;
use Laravel\Passport\ClientRepository;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Activitylog\Models\Activity;
use Tests\Concerns\GrantsAiAccess;
use Tests\TestCase;

/**
 * ai-web-research: Hausschalter `web_enabled` — Web-Recherche nur, wenn das Haus sie erlaubt (Entgrenzen P4).
 */
final class AiAssistantSettingsWebTest extends TestCase
{
    use GrantsAiAccess;

    private function actingAsAiSettingsManager(): User
    {
        return $this->actingAsUserWith(
            PermissionEnum::SETTINGS_UPDATE->value,
            User::factory()->withAiAccess()->create()
        );
    }

    /**
     * @param array<string, mixed> $overrides
     * @return array<string, mixed>
     */
    private function payload(array $overrides = []): array
    {
        return array_merge([
            'enabled' => true,
            'level_b_enabled' => true,
            'level_c_enabled' => false,
            'web_enabled' => false,
            'transcript_retention_days' => 30,
        ], $overrides);
    }

    #[Test]
    public function web_enabled_defaults_false(): void
    {
        $this->assertFalse(app(AiAssistantSettings::class)->web_enabled);

        $this->actingAsAiSettingsManager();
        $this->get(route('tool.ai-assistant'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('aiAssistantSettings.web_enabled', false));
    }

    #[Test]
    public function web_enabled_can_be_switched_on_and_is_logged(): void
    {
        $this->actingAsAiSettingsManager();

        $this->patch(route('tool.ai-assistant.update'), $this->payload(['web_enabled' => true]))
            ->assertSessionHasNoErrors();

        $this->assertTrue(app(AiAssistantSettings::class)->refresh()->web_enabled);
        $activity = Activity::query()->where('description', 'ai_assistant_settings_updated')->latest('id')->first();
        $this->assertSame(['from' => false, 'to' => true], $activity?->properties['web_enabled'] ?? null);
    }

    #[Test]
    public function web_enabled_requires_level_b(): void
    {
        $this->actingAsAiSettingsManager();

        $this->patch(route('tool.ai-assistant.update'), $this->payload([
            'level_b_enabled' => false,
            'web_enabled' => true,
        ]))->assertSessionHasErrors('web_enabled');

        $this->assertFalse(app(AiAssistantSettings::class)->refresh()->web_enabled);
    }

    #[Test]
    public function an_update_without_web_enabled_keeps_the_value(): void
    {
        $this->actingAsAiSettingsManager();
        $this->patch(route('tool.ai-assistant.update'), $this->payload(['web_enabled' => true]));

        $payload = $this->payload();
        unset($payload['web_enabled']);
        $this->patch(route('tool.ai-assistant.update'), $payload)->assertSessionHasNoErrors();

        $this->assertTrue(app(AiAssistantSettings::class)->refresh()->web_enabled);
    }

    #[Test]
    public function context_contains_web_enabled(): void
    {
        $client = app(ClientRepository::class)
            ->createPersonalAccessGrantClient('PAT client', config('auth.guards.api.provider'));
        config(['ai.enabled' => true]);
        $settings = [
            'enabled' => true,
            'level_b_enabled' => true,
            'level_c_enabled' => false,
            'web_enabled' => true,
            'transcript_retention_days' => 0,
            'ai_session_client_id' => (string) $client->getKey(),
        ];
        AiAssistantSettings::fake($settings);
        $user = $this->grantAiAccess(User::factory()->create());
        $this->withHeader('Authorization', 'Bearer ' . $user->createToken('t', [AiScopes::READ])->accessToken);

        $this->getJson('/api/v1/ai/context')->assertOk()->assertJsonPath('web_enabled', true);

        AiAssistantSettings::fake(['web_enabled' => false] + $settings);
        $this->app['auth']->forgetGuards();
        $this->getJson('/api/v1/ai/context')->assertOk()->assertJsonPath('web_enabled', false);
    }
}
