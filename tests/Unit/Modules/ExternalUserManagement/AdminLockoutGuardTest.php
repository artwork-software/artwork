<?php

namespace Tests\Unit\Modules\ExternalUserManagement;

use Artwork\Modules\ExternalUserManagement\Exceptions\AdminLockoutException;
use Artwork\Modules\ExternalUserManagement\Service\AdminLockoutGuard;
use Artwork\Modules\Role\Enums\RoleEnum;
use Artwork\Modules\User\Models\User;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

final class AdminLockoutGuardTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Der Guard zählt lokale Admins über die gesamte Tabelle. Geseedete
        // Bestandsadmins (AuthUserSeeder) würden den Zähler verfälschen, also
        // hier eine definierte Ausgangslage herstellen — die Transaktion aus
        // DatabaseTransactions rollt das nach dem Test wieder zurück.
        User::query()->update(['auth_provider' => 'oidc']);
    }

    #[Test]
    public function it_blocks_binding_the_last_local_admin(): void
    {
        $admin = $this->localAdmin('only-admin@example.com');

        $this->expectException(AdminLockoutException::class);

        app(AdminLockoutGuard::class)->assertNotLastLocalAdmin($admin);
    }

    #[Test]
    public function it_allows_binding_when_another_local_admin_remains(): void
    {
        $admin = $this->localAdmin('admin-a@example.com');
        $this->localAdmin('admin-b@example.com');

        app(AdminLockoutGuard::class)->assertNotLastLocalAdmin($admin);

        $this->assertTrue(true); // kein Wurf → ok
    }

    #[Test]
    public function it_ignores_non_admin_accounts(): void
    {
        $this->localAdmin('the-admin@example.com');
        $user = User::factory()->create(['email' => 'plain@example.com', 'auth_provider' => 'local']);

        app(AdminLockoutGuard::class)->assertNotLastLocalAdmin($user);

        $this->assertTrue(true);
    }

    private function localAdmin(string $email): User
    {
        $role = Role::query()->firstOrCreate([
            'name' => RoleEnum::ARTWORK_ADMIN->value,
            'guard_name' => 'web',
        ]);

        $user = User::factory()->create(['email' => $email, 'auth_provider' => 'local']);
        $user->assignRole($role);

        return $user;
    }
}
