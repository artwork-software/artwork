<?php

namespace Tests\Feature\Api;

use Artwork\Modules\AiAssistant\Support\AiScopes;
use Artwork\Modules\Permission\Enums\PermissionEnum;
use Artwork\Modules\Role\Enums\RoleEnum;
use Artwork\Modules\User\Models\User;
use Inertia\Testing\AssertableInertia;
use Laravel\Passport\ClientRepository;
use Laravel\Passport\Passport;
use Laravel\Passport\Token;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\Concerns\GrantsAiAccess;
use Tests\Feature\FeatureTestCase;

/**
 * KI-Freischaltung (User::hasAiAccess, Spalte users.ai_access_granted_at): zusätzliche Schranke vor
 * den KI-Routen (/api/v1/ai/*), nur per Konsole vergebbar, Voraussetzung für KI-Keys in den
 * Tooleinstellungen — und über keine Oberfläche sicht- oder vergebbar, auch nicht für Admins. Die
 * übrigen Maschinen-Routen regeln allein die Scopes.
 */
final class AiAccessPermissionTest extends FeatureTestCase
{
    use GrantsAiAccess;

    /**
     * @return array<string, array{0: string, 1: string}>
     */
    public static function gatedRoutes(): array
    {
        return [
            'ai routes' => ['/api/v1/ai/context', AiScopes::READ],
        ];
    }

    /**
     * @return array<string, array{0: string, 1: string}>
     */
    public static function machineRoutes(): array
    {
        return [
            'machine api v1 inventory' => ['/api/v1/inventory', 'inventory:read'],
            'machine api v1 rooms' => ['/api/v1/rooms', 'calendar:read'],
            'legacy inventory api' => ['/api/inventory', 'inventory:read'],
        ];
    }

    #[Test]
    #[DataProvider('machineRoutes')]
    public function machine_routes_do_not_need_the_permission(string $url, string $scope): void
    {
        Passport::actingAs(User::factory()->create(), [$scope]);

        $this->getJson($url)->assertOk();
    }

    #[Test]
    #[DataProvider('gatedRoutes')]
    public function token_owner_without_the_permission_is_forbidden(string $url, string $scope): void
    {
        Passport::actingAs(User::factory()->create(), [$scope]);

        $this->getJson($url)->assertForbidden();
    }

    #[Test]
    #[DataProvider('gatedRoutes')]
    public function token_owner_with_the_permission_passes_the_gate(string $url, string $scope): void
    {
        $this->enableAiAssistant();
        Passport::actingAs(User::factory()->withAiAccess()->create(), [$scope]);

        $this->getJson($url)->assertOk();
    }

    #[Test]
    public function the_admin_role_does_not_replace_the_permission(): void
    {
        $admin = $this->actingAsAdmin();
        Passport::actingAs($admin, [AiScopes::READ]);

        $this->getJson('/api/v1/ai/context')->assertForbidden();
    }

    #[Test]
    public function the_app_api_stays_open_without_the_permission(): void
    {
        Passport::actingAs(User::factory()->create(), ['app']);

        $this->getJson(route('app.v1.me'))->assertOk();
    }

    #[Test]
    public function saving_the_permissions_tab_keeps_ai_access(): void
    {
        $this->actingAsAdmin();
        $user = $this->grantAiAccess(User::factory()->create());

        $this->patch(route('user.update.permissions-and-roles', $user), [
            'permissions' => [PermissionEnum::PROJECT_VIEW->value],
            'roles' => [],
        ])->assertSessionMissing('error');

        $this->assertTrue($user->fresh()->hasAiAccess());
        $this->assertTrue($user->fresh()->hasDirectPermission(PermissionEnum::PROJECT_VIEW->value));
    }

    #[Test]
    public function ai_access_is_no_permission_and_cannot_be_granted_via_the_permissions_tab(): void
    {
        $this->actingAsAdmin();
        $user = User::factory()->create();

        $this->patch(route('user.update.permissions-and-roles', $user), [
            'permissions' => ['ai access', 'change ai assistant settings'],
            'roles' => [],
        ]);

        $this->assertFalse($user->fresh()->hasAiAccess());
        $this->assertDatabaseMissing('permissions', ['name' => 'ai access']);
        $this->assertDatabaseMissing('permissions', ['name' => 'change ai assistant settings']);
    }

    #[Test]
    public function ai_access_cannot_be_mass_assigned(): void
    {
        $this->actingAsAdmin();
        $user = User::factory()->create();

        $this->patch(route('user.update', $user), [
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
            'email' => $user->email,
            'ai_access_granted_at' => now()->toDateTimeString(),
        ]);

        $user->fill(['ai_access_granted_at' => now()]);

        $this->assertNull($user->ai_access_granted_at);
        $this->assertFalse($user->fresh()->hasAiAccess());
    }

    #[Test]
    public function ai_access_never_appears_in_a_user_payload(): void
    {
        $user = $this->grantAiAccess(User::factory()->create());

        $this->assertArrayNotHasKey('ai_access_granted_at', $user->fresh()->toArray());
    }

    #[Test]
    public function grant_and_revoke_work_from_the_console(): void
    {
        $user = User::factory()->create();

        $user->grantAiAccess();
        $this->assertTrue($user->fresh()->hasAiAccess());

        $user->revokeAiAccess();
        $this->assertFalse($user->fresh()->hasAiAccess());
    }

    #[Test]
    public function ai_keys_are_hidden_in_the_overview_without_the_permission(): void
    {
        $this->preparePassportClient();
        $viewer = $this->actingAsUserWith(PermissionEnum::SETTINGS_UPDATE->value);
        $viewer->createToken('Ticketshop', ['inventory:read']);
        $viewer->createToken('KI-Session', [AiScopes::READ]);

        $this->get(route('tool.interfaces'))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->has('tokens', 1)
                ->where('tokens.0.name', 'Ticketshop'));
    }

    #[Test]
    public function ai_keys_are_listed_with_the_permission(): void
    {
        $this->preparePassportClient();
        $viewer = $this->grantAiAccess($this->actingAsUserWith(PermissionEnum::SETTINGS_UPDATE->value));
        $viewer->createToken('Ticketshop', ['inventory:read']);
        $viewer->createToken('KI-Session', [AiScopes::READ]);

        $this->get(route('tool.interfaces'))
            ->assertInertia(fn (AssertableInertia $page) => $page->has('tokens', 2));
    }

    #[Test]
    public function ai_key_logs_and_revocation_are_unavailable_without_the_permission(): void
    {
        $this->preparePassportClient();
        $viewer = $this->actingAsUserWith(PermissionEnum::SETTINGS_UPDATE->value);
        $aiToken = $viewer->createToken('KI-Session', [AiScopes::READ])->token;

        $this->getJson(route('tool.interfaces.api.logs', $aiToken->getKey()))->assertNotFound();

        $this->delete(route('api-management.destroy', $aiToken->getKey()))->assertSessionHas('error');
        $this->assertFalse(Token::query()->findOrFail($aiToken->getKey())->revoked);
    }

    #[Test]
    public function migration_grants_ai_access_to_owners_of_valid_ai_keys_only(): void
    {
        $this->preparePassportClient();
        $aiKeyOwner = User::factory()->create();
        $aiKeyOwner->createToken('KI-Schlüssel', [AiScopes::READ]);
        $machineKeyOwner = User::factory()->create();
        $machineKeyOwner->createToken('Raumbelegung', ['calendar:read']);
        $revokedKeyOwner = User::factory()->create();
        $revokedKeyOwner->createToken('Alt', [AiScopes::READ])->token->revoke();

        (require base_path('database/migrations/2026_11_13_100000_move_ai_access_to_users_column.php'))->up();

        $this->assertTrue($aiKeyOwner->fresh()->hasAiAccess());
        $this->assertFalse($machineKeyOwner->fresh()->hasAiAccess());
        $this->assertFalse($revokedKeyOwner->fresh()->hasAiAccess());
    }

    private function preparePassportClient(): void
    {
        if (!file_exists(Passport::keyPath('oauth-private.key'))) {
            $this->artisan('passport:keys');
        }

        app(ClientRepository::class)->createPersonalAccessGrantClient(
            'Personal Access Client',
            config('auth.guards.api.provider')
        );
    }
}
