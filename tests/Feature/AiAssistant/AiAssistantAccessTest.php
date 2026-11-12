<?php

namespace Tests\Feature\AiAssistant;

use Artwork\Modules\AiAssistant\Services\AiAssistantAccess;
use Artwork\Modules\AiAssistant\Settings\AiAssistantSettings;
use Artwork\Modules\User\Models\User;
use PHPUnit\Framework\Attributes\Test;
use Tests\Concerns\GrantsAiAccess;
use Tests\TestCase;

/**
 * Die Freischaltung ist bewusst ein Service und kein Gate: Gate::before gibt Artwork-Admins jede
 * Gate-Abfrage frei. Der Admin-Fall unten ist deshalb der wichtigste Test des Pakets. Zusätzlich
 * muss die Person das Konsolen-Recht "ai access" direkt halten; die Admin-Rolle ersetzt es nicht.
 */
final class AiAssistantAccessTest extends TestCase
{
    use GrantsAiAccess;

    private function access(bool $instance, bool $house, bool $levelB = false, bool $levelC = false): AiAssistantAccess
    {
        config(['ai.enabled' => $instance]);

        AiAssistantSettings::fake([
            'enabled' => $house,
            'level_b_enabled' => $levelB,
            'level_c_enabled' => $levelC,
            'transcript_retention_days' => 0,
            'ai_session_client_id' => null,
        ]);

        return app(AiAssistantAccess::class);
    }

    #[Test]
    public function not_allowed_when_the_instance_switch_is_off(): void
    {
        $this->assertFalse($this->access(instance: false, house: true)->allowed(User::factory()->create()));
    }

    #[Test]
    public function not_allowed_when_the_house_setting_is_off(): void
    {
        $this->assertFalse($this->access(instance: true, house: false)->allowed(User::factory()->create()));
    }

    #[Test]
    public function allowed_only_when_instance_and_house_are_on(): void
    {
        $this->assertTrue(
            $this->access(instance: true, house: true)->allowed(User::factory()->withAiAccess()->create())
        );
    }

    #[Test]
    public function not_allowed_without_the_console_permission(): void
    {
        $this->assertFalse($this->access(instance: true, house: true)->allowed(User::factory()->create()));
    }

    #[Test]
    public function an_admin_without_the_console_permission_is_not_allowed(): void
    {
        $this->assertFalse($this->access(instance: true, house: true, levelB: true, levelC: true)
            ->allowed($this->adminUser()));
    }

    #[Test]
    public function an_admin_with_the_console_permission_is_allowed(): void
    {
        $admin = $this->grantAiAccess($this->adminUser());

        $this->assertTrue($this->access(instance: true, house: true)->allowed($admin));
    }

    #[Test]
    public function not_allowed_without_a_user(): void
    {
        $this->assertFalse($this->access(instance: true, house: true)->allowed(null));
    }

    #[Test]
    public function an_admin_is_not_allowed_while_the_house_is_off(): void
    {
        $admin = $this->grantAiAccess($this->adminUser());

        $this->assertFalse($this->access(instance: true, house: false)->allowed($admin));
        $this->assertFalse($this->access(instance: false, house: true)->allowed($admin));
    }

    #[Test]
    public function the_stages_require_the_assistant_to_be_released(): void
    {
        $user = User::factory()->create();

        $access = $this->access(instance: true, house: false, levelB: true, levelC: true);
        $this->assertFalse($access->levelBAllowed($user));
        $this->assertFalse($access->levelCAllowed($user));
    }

    #[Test]
    public function stage_c_requires_stage_b(): void
    {
        $user = User::factory()->withAiAccess()->create();

        $access = $this->access(instance: true, house: true, levelB: false, levelC: true);
        $this->assertFalse($access->levelCAllowed($user));

        $access = $this->access(instance: true, house: true, levelB: true, levelC: true);
        $this->assertTrue($access->levelCAllowed($user));
    }
}
