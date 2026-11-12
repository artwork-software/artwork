<?php

namespace Tests\Feature\Api;

use Artwork\Modules\AiAssistant\Support\AiScopes;
use Artwork\Modules\Permission\Enums\ConsoleOnlyPermissionEnum;
use Artwork\Modules\Permission\Enums\PermissionEnum;
use Artwork\Modules\Role\Enums\RoleEnum;
use Artwork\Modules\User\Models\User;
use Inertia\Testing\AssertableInertia;
use Laravel\Passport\ClientRepository;
use Laravel\Passport\Passport;
use Laravel\Passport\Token;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\Concerns\GrantsAiAccess;
use Tests\Feature\FeatureTestCase;

/**
 * Konsolen-Recht "ai access" (ConsoleOnlyPermissionEnum::AiAccess): zusätzliche Schranke vor
 * Maschinen-API und KI-Routen, nur per Tinker vergebbar, und Voraussetzung, um KI-Keys in den
 * Tooleinstellungen zu sehen.
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
            'machine api v1' => ['/api/v1/inventory', 'inventory:read'],
            'legacy inventory api' => ['/api/inventory', 'inventory:read'],
            'ai routes' => ['/api/v1/ai/context', AiScopes::READ],
        ];
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
        Passport::actingAs(User::factory()->withAiAccess()->create(), [$scope]);

        $this->getJson($url)->assertOk();
    }

    #[Test]
    public function the_admin_role_does_not_replace_the_permission(): void
    {
        $admin = $this->actingAsAdmin();
        Passport::actingAs($admin, ['inventory:read']);

        $this->getJson('/api/v1/inventory')->assertForbidden();
    }

    #[Test]
    public function the_permission_on_a_role_does_not_count(): void
    {
        $role = Role::findOrCreate('api-role', 'web');
        $role->givePermissionTo(Permission::findOrCreate(ConsoleOnlyPermissionEnum::AiAccess->value, 'web'));
        $user = User::factory()->create();
        $user->assignRole($role);

        Passport::actingAs($user, ['inventory:read']);

        $this->getJson('/api/v1/inventory')->assertForbidden();
    }

    #[Test]
    public function the_app_api_stays_open_without_the_permission(): void
    {
        Passport::actingAs(User::factory()->create(), ['app']);

        $this->getJson(route('app.v1.me'))->assertOk();
    }

    #[Test]
    public function saving_the_permissions_tab_keeps_the_permission(): void
    {
        $this->actingAsAdmin();
        $user = $this->grantAiAccess(User::factory()->create());

        $this->patch(route('user.update.permissions-and-roles', $user), [
            'permissions' => [PermissionEnum::PROJECT_VIEW->value],
            'roles' => [],
        ])->assertSessionMissing('error');

        $user->unsetRelation('permissions');
        $this->assertTrue($user->hasAiAccess());
        $this->assertTrue($user->hasDirectPermission(PermissionEnum::PROJECT_VIEW->value));
    }

    #[Test]
    public function the_permissions_tab_cannot_grant_the_permission(): void
    {
        $this->actingAsAdmin();
        $user = User::factory()->create();

        $this->patch(route('user.update.permissions-and-roles', $user), [
            'permissions' => [ConsoleOnlyPermissionEnum::AiAccess->value],
            'roles' => [],
        ]);

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
