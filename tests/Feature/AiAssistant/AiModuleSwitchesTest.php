<?php

namespace Tests\Feature\AiAssistant;

use App\Settings\ShiftSettings;
use Artwork\Modules\AiAssistant\Http\Middleware\EnsureAiHouseSwitches;
use Artwork\Modules\AiAssistant\Settings\AiAssistantSettings;
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

        $this->assistant();
    }

    /**
     * Schalter des Assistenten: Betreiber (`ai.enabled`), Haus, Stufe B und C.
     */
    private function assistant(bool $operator = true, bool $house = true, bool $levelB = true, bool $levelC = true): void
    {
        config(['ai.enabled' => $operator]);
        AiAssistantSettings::fake([
            'enabled' => $house,
            'level_b_enabled' => $levelB,
            'level_c_enabled' => $levelC,
            'transcript_retention_days' => 0,
            'ai_session_client_id' => null,
        ]);
    }

    /** @param string[] $scopes */
    private function actingAsToken(User $user, array $scopes = [AiScopes::READ]): void
    {
        $this->app['auth']->forgetGuards();
        $token = $this->grantAiAccess($user)->createToken('ai-session-test', $scopes);
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
            'Auslastung' => ['/api/v1/ai/people/user/7/workload', 'shift_plan'],
            'Personensuche' => ['/api/v1/ai/people', 'shift_plan'],
            'Abwesenheitsliste' => ['/api/v1/ai/absences', 'shift_plan'],
            'Eigene Stunden' => ['/api/v1/ai/people/user/7/work-time', null],
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
    public function creating_an_absence_is_not_bound_to_the_shift_plan_module(): void
    {
        $this->assertNull(EnsureAiHouseSwitches::moduleFor('/api/v1/ai/absences', 'POST'));
        $this->assertSame('shift_plan', EnsureAiHouseSwitches::moduleFor('/api/v1/ai/absences', 'GET'));
    }

    #[Test]
    public function people_search_and_absence_list_follow_the_shift_plan_module(): void
    {
        // Sicherheits-Audit 18.11.2026, N6
        $planner = User::factory()->create();
        foreach ([PermissionEnum::SHIFT_PLANNER->value, PermissionEnum::VIEW_SHIFT_PLAN->value] as $name) {
            $planner->givePermissionTo(Permission::findOrCreate($name, 'web'));
        }
        $this->actingAsToken($planner->fresh());

        $this->setModule('shift_plan', true);
        $this->getJson('/api/v1/ai/people')->assertOk();
        $this->getJson('/api/v1/ai/absences?from=2026-11-02&to=2026-11-08')->assertOk();
        $this->getJson('/api/v1/ai/day-overview?date=2026-11-02')
            ->assertOk()
            ->assertJsonStructure(['planner' => ['open_shifts', 'absences', 'room_conflicts']]);

        $this->setModule('shift_plan', false);
        $this->getJson('/api/v1/ai/people')->assertForbidden();
        $this->getJson('/api/v1/ai/absences?from=2026-11-02&to=2026-11-08')->assertForbidden();
        $this->getJson('/api/v1/ai/day-overview?date=2026-11-02')->assertOk()->assertJsonPath('planner', null);
    }

    // --- Sicherheits-Audit 18.11.2026, M7: Schalter des Assistenten an /api/v1/ai/* ---------------

    /**
     * @return array<string, array{0: bool, 1: bool}>
     */
    public static function assistantOff(): array
    {
        return [
            'Betreiber aus' => [false, true],
            'Haus aus' => [true, false],
        ];
    }

    #[Test]
    #[DataProvider('assistantOff')]
    public function a_switched_off_assistant_blocks_existing_tokens(bool $operator, bool $house): void
    {
        $user = User::factory()->create();
        $this->actingAsToken($user, [AiScopes::READ, AiScopes::TRANSCRIPT]);
        $this->getJson('/api/v1/ai/context')->assertOk();

        $this->assistant($operator, $house);

        $this->getJson('/api/v1/ai/context')->assertForbidden();
        $this->getJson('/api/v1/ai/events?from=2026-11-02&to=2026-11-08')->assertForbidden();
        $this->postJson('/api/v1/ai/conversations', [])->assertForbidden();
        // Der oeffentliche Schluessel bleibt erreichbar
        $this->withoutHeader('Authorization')->getJson('/api/v1/ai/jwks')->assertOk();
    }

    #[Test]
    public function without_level_b_only_the_chat_history_stays_reachable(): void
    {
        $user = User::factory()->create();
        $this->assistant(levelB: false, levelC: false);
        $this->actingAsToken($user, [AiScopes::READ, AiScopes::TRANSCRIPT]);

        $this->getJson('/api/v1/ai/context')->assertForbidden();
        $this->getJson('/api/v1/ai/events?from=2026-11-02&to=2026-11-08')->assertForbidden();
        $this->patchJson('/api/v1/ai/events/1', ['dry_run' => true])->assertForbidden();

        $conversation = $this->postJson('/api/v1/ai/conversations', ['title' => 'Hallo']);
        $this->assertNotSame(403, $conversation->getStatusCode(), (string) $conversation->getContent());
    }

    #[Test]
    public function without_level_c_previews_work_but_executions_and_actions_do_not(): void
    {
        $user = User::factory()->create();
        $this->assistant(levelC: false);
        $this->actingAsToken($user, [AiScopes::READ, AiScopes::WRITE]);

        $this->getJson('/api/v1/ai/context')->assertOk();
        // Vorschau: kommt an der Schalterpruefung vorbei (404 = unbekannter Termin, nicht 403)
        $this->patchJson('/api/v1/ai/events/999999', ['dry_run' => true, 'name' => 'x'])->assertNotFound();
        $this->patchJson('/api/v1/ai/events/999999', ['dry_run' => false, 'name' => 'x'])->assertForbidden();
        $this->getJson('/api/v1/ai/actions/' . \Illuminate\Support\Str::uuid())->assertForbidden();

        // Mit Stufe C kommt die Ausfuehrung wieder bis zur Eingabepruefung (action_id fehlt)
        $this->assistant();
        $this->app['auth']->forgetGuards();
        $this->patchJson('/api/v1/ai/events/999999', ['dry_run' => false, 'name' => 'x'])->assertUnprocessable();
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
