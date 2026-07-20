<?php

namespace Tests\Feature\ExternalUserManagement;

use Artwork\Modules\ExternalUserManagement\Exceptions\LdapAuthenticationFailedException;
use Artwork\Modules\ExternalUserManagement\Models\ExternalUserSource;
use Artwork\Modules\ExternalUserManagement\Service\CredentialLoginService;
use Artwork\Modules\ExternalUserManagement\Service\LdapService;
use Artwork\Modules\User\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

final class CredentialLoginServiceTest extends FeatureTestCase
{
    #[Test]
    public function a_local_account_authenticates_with_its_password(): void
    {
        $user = User::factory()->create([
            'email' => 'local@example.com',
            'password' => Hash::make('secret-password'),
            'auth_provider' => 'local',
        ]);

        $resolved = app(CredentialLoginService::class)->attempt('local@example.com', 'secret-password');

        $this->assertNotNull($resolved);
        $this->assertTrue($user->is($resolved));
    }

    #[Test]
    public function an_oidc_account_is_blocked_at_the_password_form(): void
    {
        User::factory()->create([
            'email' => 'sso@example.com',
            'password' => Hash::make('whatever'),
            'auth_provider' => 'oidc',
            'auth_provider_id' => 'subject-1',
        ]);

        $this->expectException(ValidationException::class);

        app(CredentialLoginService::class)->attempt('sso@example.com', 'whatever');
    }

    #[Test]
    public function an_unknown_user_is_provisioned_via_a_successful_ldap_bind(): void
    {
        $source = ExternalUserSource::query()->create([
            'name' => 'Directory',
            'active' => true,
            'type' => 'ldap',
            'config' => ['host' => 'ldap.example.test', 'base_dn' => 'dc=example,dc=test'],
        ]);

        $this->mock(LdapService::class, function ($mock): void {
            $mock->shouldReceive('authenticateAndFetch')->andReturn([
                'identifier' => 'entry-uuid-1',
                'email' => 'new@example.com',
                'first_name' => 'New',
                'last_name' => 'User',
                'groups' => [],
                'email_verified' => true,
            ]);
        });

        $resolved = app(CredentialLoginService::class)->attempt('new@example.com', 'directory-password');

        $this->assertNotNull($resolved);
        $this->assertSame('ldap', $resolved->auth_provider);
        $this->assertSame('entry-uuid-1', $resolved->auth_provider_id);
        $this->assertSame('new@example.com', $resolved->email);
    }

    #[Test]
    public function a_failing_ldap_source_does_not_abort_login_against_a_later_source(): void
    {
        // Erste (unerreichbare) Quelle wirft, zweite Quelle authentifiziert erfolgreich.
        foreach (['Broken', 'Working'] as $name) {
            ExternalUserSource::query()->create([
                'name' => $name,
                'active' => true,
                'type' => 'ldap',
                'config' => ['host' => strtolower($name) . '.example.test', 'base_dn' => 'dc=example,dc=test'],
            ]);
        }

        $this->mock(LdapService::class, function ($mock): void {
            $mock->shouldReceive('authenticateAndFetch')
                ->once()
                ->andThrow(new \RuntimeException('Can\'t contact LDAP server'));
            $mock->shouldReceive('authenticateAndFetch')
                ->once()
                ->andReturn([
                    'identifier' => 'entry-uuid-2',
                    'email' => 'resilient@example.com',
                    'first_name' => 'Res',
                    'last_name' => 'Ilient',
                    'groups' => [],
                    'email_verified' => true,
                ]);
        });

        $resolved = app(CredentialLoginService::class)->attempt('resilient@example.com', 'pw');

        $this->assertNotNull($resolved);
        $this->assertSame('ldap', $resolved->auth_provider);
        $this->assertSame('entry-uuid-2', $resolved->auth_provider_id);
    }

    #[Test]
    public function a_user_absent_from_the_directory_falls_back_to_standard_local_login(): void
    {
        // Nutzer existiert nicht im IdP → Login verhält sich wie im Standard.
        ExternalUserSource::query()->create([
            'name' => 'Directory',
            'active' => true,
            'type' => 'ldap',
            'config' => ['host' => 'ldap.example.test', 'base_dn' => 'dc=example,dc=test'],
        ]);

        User::factory()->create([
            'email' => 'local@example.com',
            'password' => Hash::make('correct'),
            'auth_provider' => 'local',
        ]);

        $this->mock(LdapService::class, function ($mock): void {
            // Nutzer nicht im Verzeichnis vorhanden.
            $mock->shouldReceive('authenticateAndFetch')->andReturn(null);
        });

        $svc = app(CredentialLoginService::class);

        $ok = $svc->attempt('local@example.com', 'correct');
        $this->assertNotNull($ok);
        $this->assertSame('local@example.com', $ok->email);

        $this->assertNull($svc->attempt('local@example.com', 'wrong'));
    }

    #[Test]
    public function a_user_present_in_the_directory_is_authenticated_only_by_the_idp(): void
    {
        // Nutzer existiert im IdP UND hat (noch) ein lokales Passwort. Bei falschem
        // Directory-Passwort darf der lokale Login NICHT als Fallback greifen.
        ExternalUserSource::query()->create([
            'name' => 'Directory',
            'active' => true,
            'type' => 'ldap',
            'config' => ['host' => 'ldap.example.test', 'base_dn' => 'dc=example,dc=test'],
        ]);

        User::factory()->create([
            'email' => 'hybrid@example.com',
            'password' => Hash::make('local-password'),
            'auth_provider' => 'local',
        ]);

        $this->mock(LdapService::class, function ($mock): void {
            // Im Verzeichnis vorhanden, aber Bind schlägt fehl.
            $mock->shouldReceive('authenticateAndFetch')
                ->andThrow(new LdapAuthenticationFailedException('bind failed'));
        });

        // Obwohl das lokale Passwort stimmt, wird der Login abgelehnt – nur IdP zählt.
        $this->assertNull(
            app(CredentialLoginService::class)->attempt('hybrid@example.com', 'local-password')
        );
    }
}
