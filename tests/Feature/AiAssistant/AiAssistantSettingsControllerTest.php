<?php

namespace Tests\Feature\AiAssistant;

use Artwork\Modules\AiAssistant\Settings\AiAssistantSettings;
use Artwork\Modules\Permission\Enums\PermissionEnum;
use Artwork\Modules\User\Models\User;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Activitylog\Models\Activity;
use Tests\TestCase;

final class AiAssistantSettingsControllerTest extends TestCase
{
    /**
     * @return array<string, mixed>
     */
    private function payload(array $overrides = []): array
    {
        return array_merge([
            'enabled' => true,
            'level_b_enabled' => true,
            'level_c_enabled' => false,
            'transcript_retention_days' => 30,
        ], $overrides);
    }

    #[Test]
    public function a_user_without_the_permission_cannot_open_the_page(): void
    {
        $this->actingAs(User::factory()->create());

        $this->get(route('tool.ai-assistant'))->assertForbidden();
    }

    #[Test]
    public function tool_settings_permission_alone_is_not_enough(): void
    {
        $this->actingAsUserWith(PermissionEnum::SETTINGS_UPDATE->value);

        $this->get(route('tool.ai-assistant'))->assertForbidden();
    }

    #[Test]
    public function a_user_with_the_permission_can_open_the_page(): void
    {
        $this->actingAsUserWith(PermissionEnum::AI_ASSISTANT_SETTINGS_UPDATE->value);

        $this->get(route('tool.ai-assistant'))->assertOk();
    }

    #[Test]
    public function a_user_without_the_permission_cannot_update(): void
    {
        $this->actingAs(User::factory()->create());

        $this->patch(route('tool.ai-assistant.update'), $this->payload())->assertForbidden();
    }

    #[Test]
    public function the_settings_are_stored(): void
    {
        $this->actingAsUserWith(PermissionEnum::AI_ASSISTANT_SETTINGS_UPDATE->value);

        $this->patch(route('tool.ai-assistant.update'), $this->payload([
            'level_c_enabled' => true,
            'transcript_retention_days' => 90,
        ]))->assertRedirect();

        $settings = app(AiAssistantSettings::class);
        $this->assertTrue($settings->enabled);
        $this->assertTrue($settings->level_b_enabled);
        $this->assertTrue($settings->level_c_enabled);
        $this->assertSame(90, $settings->transcript_retention_days);
    }

    #[Test]
    public function stage_c_without_stage_b_is_rejected(): void
    {
        $this->actingAsUserWith(PermissionEnum::AI_ASSISTANT_SETTINGS_UPDATE->value);

        $this->patch(route('tool.ai-assistant.update'), $this->payload([
            'level_b_enabled' => false,
            'level_c_enabled' => true,
        ]))->assertSessionHasErrors('level_c_enabled');
    }

    #[Test]
    public function switching_the_assistant_on_is_logged(): void
    {
        $this->actingAsUserWith(PermissionEnum::AI_ASSISTANT_SETTINGS_UPDATE->value);

        $this->patch(route('tool.ai-assistant.update'), $this->payload(['enabled' => true]));

        $this->assertTrue(
            Activity::query()->where('description', 'ai_assistant_settings_updated')->exists()
        );
    }

    #[Test]
    public function changing_only_the_retention_period_is_not_logged(): void
    {
        $this->actingAsUserWith(PermissionEnum::AI_ASSISTANT_SETTINGS_UPDATE->value);

        // Erst den Ausgangszustand herstellen, dann nur die Aufbewahrungsdauer aendern.
        $this->patch(route('tool.ai-assistant.update'), $this->payload());
        Activity::query()->where('description', 'ai_assistant_settings_updated')->delete();

        $this->patch(route('tool.ai-assistant.update'), $this->payload([
            'transcript_retention_days' => 7,
        ]));

        $this->assertFalse(
            Activity::query()->where('description', 'ai_assistant_settings_updated')->exists()
        );
    }
}
