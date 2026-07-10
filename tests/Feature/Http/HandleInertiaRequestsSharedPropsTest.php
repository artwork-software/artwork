<?php

namespace Tests\Feature\Http;

use Artwork\Core\Http\Middleware\HandleInertiaRequests;
use Artwork\Modules\Permission\Models\Permission;
use Artwork\Modules\Role\Enums\RoleEnum;
use Artwork\Modules\User\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;
use Tests\Feature\FeatureTestCase;

/**
 * Regression für das Rollen-/Permission-Caching in HandleInertiaRequests:
 * Der 'permissions'-Prop muss die frühere jsPermissions()-Struktur behalten,
 * Admins bekommen weiterhin ALLE Permission-Namen, und Rollenänderungen
 * invalidieren den Cache über User::forgetCachedShareData() sofort.
 */
final class HandleInertiaRequestsSharedPropsTest extends FeatureTestCase
{
    private function share(): array
    {
        return app(HandleInertiaRequests::class)->share(Request::create('/dashboard', 'GET'));
    }

    private function seedRolesIfMissing(): void
    {
        if (!Role::where('name', RoleEnum::ARTWORK_ADMIN->value)->exists()) {
            $this->artisan('db:seed', ['--class' => RolesAndPermissionsSeeder::class]);
        }
    }

    #[Test]
    public function admin_gets_all_permission_names_and_js_permissions_structure(): void
    {
        $admin = $this->adminUser();
        $this->actingAs($admin);

        $shared = $this->share();

        $this->assertContains(RoleEnum::ARTWORK_ADMIN->value, $shared['rolesArray']);

        // Admin-Sonderfall: permissionsArray = ALLE Permission-Namen (wie vorher Permission::all())
        $this->assertEqualsCanonicalizing(
            Permission::query()->pluck('name')->toArray(),
            (array) $shared['permissionsArray']
        );

        // Struktur des 'permissions'-Props muss der früheren jsPermissions()-Ausgabe entsprechen
        $this->assertIsArray($shared['permissions']);
        $this->assertArrayHasKey('roles', $shared['permissions']);
        $this->assertArrayHasKey('permissions', $shared['permissions']);
        $this->assertContains(RoleEnum::ARTWORK_ADMIN->value, $shared['permissions']['roles']);

        $this->assertTrue($shared['canSeeIncomingRequests']);
    }

    #[Test]
    public function plain_user_gets_only_own_permissions_and_no_incoming_requests(): void
    {
        $this->seedRolesIfMissing();
        $user = User::factory()->create();
        $permission = Permission::query()->firstOrFail();
        $user->givePermissionTo($permission->name);
        $this->actingAs($user);

        $shared = $this->share();

        $this->assertSame([], $shared['rolesArray']);
        $this->assertSame([$permission->name], (array) $shared['permissionsArray']);

        // Ein User ohne Rolle/Raum-Admin/Event-Verifier sieht keine eingehenden Anfragen —
        // Regression: Cache::remember muss auch ein false-Ergebnis korrekt liefern
        if ($permission->name !== 'can create events without request') {
            $this->assertFalse($shared['canSeeIncomingRequests']);
        }
    }

    #[Test]
    public function share_data_is_cached_per_user_and_busted_on_role_change(): void
    {
        $admin = $this->adminUser();
        $this->actingAs($admin);

        $this->share();

        $rolesKey = "user:{$admin->id}:roles_permissions";
        $incomingKey = "user:{$admin->id}:can_see_incoming_requests";
        $this->assertTrue(Cache::has($rolesKey));
        $this->assertTrue(Cache::has($incomingKey));

        // Rollenänderung muss die gecachten Share-Daten sofort invalidieren
        $admin->forgetCachedShareData();
        $this->assertFalse(Cache::has($rolesKey));
        $this->assertFalse(Cache::has($incomingKey));
        $this->assertFalse(Cache::has("user:{$admin->id}:shift_workflow_flags"));
    }

    #[Test]
    public function guest_gets_empty_permission_props(): void
    {
        $shared = $this->share();

        $this->assertSame([], $shared['rolesArray']);
        $this->assertSame([], (array) $shared['permissionsArray']);
        $this->assertSame([], $shared['permissions']);
        $this->assertFalse($shared['canSeeIncomingRequests']);
    }
}
