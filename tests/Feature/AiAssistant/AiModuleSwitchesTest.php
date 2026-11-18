<?php

namespace Tests\Feature\AiAssistant;

use App\Settings\ShiftSettings;
use Artwork\Modules\AiAssistant\Http\Middleware\EnsureAiHouseSwitches;
use Artwork\Modules\AiAssistant\Support\AiScopes;
use Artwork\Modules\ModuleSettings\Models\ModuleSettings;
use Artwork\Modules\Permission\Enums\PermissionEnum;
use Artwork\Modules\Role\Enums\RoleEnum;
use Artwork\Modules\User\Models\User;
use Laravel\Passport\ClientRepository;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\Concerns\GrantsAiAccess;
use Tests\TestCase;

/**
 * Die KI handelt mit den Rechten der Person: abgeschaltete Module und die abgeschaltete Arbeitszeitberechnung
 * gelten auch für die KI-Endpunkte (Admins wie in der Oberfläche ausgenommen).
 */
final class AiModuleSwitchesTest extends TestCase
{
    use GrantsAiAccess;

    protected function setUp(): void
    {
        parent::setUp();

        app(ClientRepository::class)
            ->createPersonalAccessGrantClient('PAT client', config('auth.guards.api.provider'));
    }

    private function actingAsToken(User $user): void
    {
        $this->app['auth']->forgetGuards();
        $token = $this->grantAiAccess($user)->createToken('ai-session-test', [AiScopes::READ]);
        $this->withHeader('Authorization', 'Bearer ' . $token->accessToken);
    }

    private function contractViewer(): User
    {
        $user = User::factory()->create();
        $user->givePermissionTo(Permission::findOrCreate(PermissionEnum::CONTRACT_SEE_DOWNLOAD->value, 'web'));

        return $user->fresh();
    }

    private function setModule(string $module, bool $visible): void
    {
        $settings = app(ModuleSettings::class);
        $settings->{$module} = $visible;
        $settings->save();
    }

    /**
     * @return array<string, array{0: string, 1: string|null}>
     */
    public static function paths(): array
    {
        return [
            'Dienstplan' => ['/api/v1/ai/shift-plan', 'shift_plan'],
            'Schichtzuweisung' => ['/api/v1/ai/shift-assignments/bulk', 'shift_plan'],
            'Termin-Schichten' => ['/api/v1/ai/events/5/shifts', 'shift_plan'],
            'Inventar' => ['/api/v1/ai/inventory/availability', 'inventory'],
            'Verträge' => ['/api/v1/ai/contracts', 'contracts'],
            'Aufgaben' => ['/api/v1/ai/me/tasks', 'tasks'],
            'Termine' => ['/api/v1/ai/events', null],
            'Projekte' => ['/api/v1/ai/projects/3', null],
        ];
    }

    #[Test]
    #[DataProvider('paths')]
    public function ai_paths_map_to_the_module_switches_of_the_ui(string $path, ?string $module): void
    {
        $this->assertSame($module, EnsureAiHouseSwitches::moduleFor($path));
    }

    #[Test]
    public function a_disabled_module_is_not_reachable_via_the_assistant(): void
    {
        $this->setModule('contracts', false);
        $this->actingAsToken($this->contractViewer());

        $this->getJson('/api/v1/ai/contracts')->assertForbidden();
    }

    #[Test]
    public function an_enabled_module_stays_reachable(): void
    {
        $this->setModule('contracts', true);
        $this->actingAsToken($this->contractViewer());

        $this->getJson('/api/v1/ai/contracts')->assertOk();
    }

    #[Test]
    public function admins_pass_a_disabled_module_like_in_the_ui(): void
    {
        $this->setModule('contracts', false);
        $admin = User::factory()->create();
        $admin->assignRole(Role::findOrCreate(RoleEnum::ARTWORK_ADMIN->value, 'web'));
        $this->actingAsToken($admin->fresh());

        $this->getJson('/api/v1/ai/contracts')->assertOk();
    }

    #[Test]
    public function work_time_figures_need_the_work_time_accounting(): void
    {
        $settings = app(ShiftSettings::class);
        $settings->work_time_accounting_enabled = false;
        $settings->save();
        $this->actingAsToken(User::factory()->create());

        $this->getJson('/api/v1/ai/work-time/overview?month=2026-11')->assertForbidden();
    }
}
