<?php

namespace Tests\Feature\AiAssistant;

use Artwork\Core\Http\Middleware\HandleInertiaRequests;
use Artwork\Modules\AiAssistant\Settings\AiAssistantSettings;
use Artwork\Modules\User\Models\User;
use Illuminate\Http\Request;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

final class AiAssistantSharedPropsTest extends FeatureTestCase
{
    use GrantsAiAccess;

    /**
     * @return array<string, mixed>
     */
    private function share(): array
    {
        return app(HandleInertiaRequests::class)->share(Request::create('/dashboard', 'GET'));
    }

    private function settings(bool $instance, bool $house, bool $levelB = false, bool $levelC = false): void
    {
        config(['ai.enabled' => $instance]);

        AiAssistantSettings::fake([
            'enabled' => $house,
            'level_b_enabled' => $levelB,
            'level_c_enabled' => $levelC,
            'transcript_retention_days' => 0,
            'ai_session_client_id' => null,
        ]);
    }

    #[Test]
    public function a_disabled_assistant_shares_nothing_but_the_flag(): void
    {
        $this->actingAs(User::factory()->create());
        $this->settings(instance: true, house: false, levelB: true, levelC: true);

        $this->assertSame(['enabled' => false], $this->share()['ai']);
    }

    #[Test]
    public function an_admin_also_only_sees_the_flag_while_the_house_is_off(): void
    {
        $this->actingAs($this->adminUser());
        $this->settings(instance: true, house: false);

        $this->assertSame(['enabled' => false], $this->share()['ai']);
    }

    /** Mit Freischaltung bleiben Einstellungs-Reiter und KI-Hinweise sichtbar, auch bei abgeschaltetem Haus. */
    #[Test]
    public function with_ai_access_the_assistant_stays_visible_while_the_house_is_off(): void
    {
        $this->actingAs($this->grantAiAccess($this->adminUser()));
        $this->settings(instance: true, house: false, levelB: true, levelC: true);

        $this->assertSame(['visible' => true, 'enabled' => false], $this->share()['ai']);
    }

    #[Test]
    public function a_user_without_the_console_permission_only_sees_the_flag(): void
    {
        $this->actingAs(User::factory()->create());
        $this->settings(instance: true, house: true, levelB: true, levelC: true);

        $this->assertSame(['enabled' => false], $this->share()['ai']);
    }

    #[Test]
    public function an_admin_without_the_console_permission_only_sees_the_flag(): void
    {
        $this->actingAs($this->adminUser());
        $this->settings(instance: true, house: true, levelB: true, levelC: true);

        $this->assertSame(['enabled' => false], $this->share()['ai']);
    }

    #[Test]
    public function a_released_assistant_shares_its_stages(): void
    {
        $this->actingAs(User::factory()->withAiAccess()->create());
        $this->settings(instance: true, house: true, levelB: true, levelC: true);

        $this->assertSame(
            ['visible' => true, 'enabled' => true, 'levelB' => true, 'levelC' => true],
            $this->share()['ai']
        );
    }

    #[Test]
    public function stages_are_false_when_they_are_not_released(): void
    {
        $this->actingAs(User::factory()->withAiAccess()->create());
        $this->settings(instance: true, house: true);

        $this->assertSame(
            ['visible' => true, 'enabled' => true, 'levelB' => false, 'levelC' => false],
            $this->share()['ai']
        );
    }
}
