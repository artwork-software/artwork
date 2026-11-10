<?php

namespace Tests\Feature;

use Artwork\Core\Mail\MailService;
use Artwork\Modules\ExternalUserManagement\Models\ExternalUser;
use Artwork\Modules\ExternalUserManagement\Models\ExternalUserSource;
use Artwork\Modules\ExternalUserManagement\Service\ExternalUserSyncService;
use Artwork\Modules\ExternalUserManagement\Service\LdapService;
use Artwork\Modules\ExternalUserManagement\Service\OidcService;
use Artwork\Modules\User\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Laravel\Passport\ClientRepository;
use Laravel\Passport\Passport;
use Laravel\Passport\Token;
use PHPUnit\Framework\Attributes\Test;

/**
 * Audit 20.10.2026, Paket 3 (M1, M2): Konten aus OIDC/LDAP entstehen nur noch aus vertrauenswürdigen
 * Quellen. OIDC: keine Entra-Multi-Tenant-Endpunkte, Neuanlage nur mit email_verified oder
 * ausdrücklichem Vertrauen, Kennung nur sub/oid. LDAP-Sync: Domain-Allowlist, in artwork gelöschte
 * Personen werden nicht neu angelegt.
 */
final class SecurityAuditIdpProvisioningRegressionTest extends FeatureTestCase
{
    /**
     * @param array<string, mixed> $config
     */
    private function oidcSource(array $config = []): ExternalUserSource
    {
        return ExternalUserSource::query()->create([
            'name' => 'IdP',
            'active' => true,
            'type' => 'identity_provider',
            'config' => array_merge([
                'discovery_url' => 'https://idp.example.com/.well-known/openid-configuration',
                'client_id' => 'artwork',
                'client_secret' => 'secret',
                'allowed_domains' => ['example.com'],
            ], $config),
        ]);
    }

    /**
     * @param array<string, mixed> $overrides
     */
    private function fakeOidcLogin(array $overrides = []): void
    {
        $claims = array_merge([
            'identifier' => 'subject-123',
            'email' => 'new.person@example.com',
            'first_name' => 'Neu',
            'last_name' => 'Person',
            'groups' => [],
            'email_verified' => false,
            'meta_data' => ['sub' => 'subject-123'],
        ], $overrides);

        $this->mock(OidcService::class, function ($mock) use ($claims): void {
            $mock->shouldReceive('userFromCallback')->andReturn($claims);
            $mock->shouldReceive('redirect')->andReturn(redirect('https://idp.example.com/authorize'));
        });
    }

    /**
     * @param array<string, mixed> $config
     * @return array<string, mixed>
     */
    private function oidcPayload(array $config): array
    {
        return [
            'name' => 'Neue Quelle',
            'active' => true,
            'type' => 'identity_provider',
            'config' => array_merge([
                'client_id' => 'artwork',
                'client_secret' => 'secret',
                'allowed_domains' => ['example.com'],
            ], $config),
        ];
    }

    #[Test]
    public function an_unverified_oidc_email_does_not_create_an_account_without_trust(): void
    {
        $source = $this->oidcSource();
        $this->fakeOidcLogin();

        $this->get(route('auth.oidc.callback', ['externalUserSource' => $source->id]))
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors(['email' => __('flash-messages.oidc.error.email_not_verified')]);

        $this->assertFalse(Auth::check());
        $this->assertDatabaseMissing('users', ['email' => 'new.person@example.com']);
    }

    #[Test]
    public function a_trusted_source_creates_accounts_without_email_verified(): void
    {
        $source = $this->oidcSource(['trust_idp_email' => true]);
        $this->fakeOidcLogin();

        $this->get(route('auth.oidc.callback', ['externalUserSource' => $source->id]))
            ->assertSessionHasNoErrors();

        $this->assertTrue(Auth::check());
        $this->assertDatabaseHas('users', ['email' => 'new.person@example.com']);
    }

    #[Test]
    public function a_verified_oidc_email_still_creates_an_account(): void
    {
        $source = $this->oidcSource();
        $this->fakeOidcLogin(['email_verified' => true]);

        $this->get(route('auth.oidc.callback', ['externalUserSource' => $source->id]));

        $this->assertTrue(Auth::check());
        $this->assertDatabaseHas('users', ['email' => 'new.person@example.com']);
    }

    #[Test]
    public function multi_tenant_sources_are_rejected_at_redirect_and_callback(): void
    {
        $source = $this->oidcSource([
            'provider_preset' => 'microsoft',
            'tenant_id' => 'common',
            'discovery_url' => 'https://login.microsoftonline.com/common/v2.0/.well-known/openid-configuration',
            'trust_idp_email' => true,
        ]);
        $this->fakeOidcLogin(['email_verified' => true]);

        $this->get(route('auth.oidc.redirect', ['externalUserSource' => $source->id]))
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors(['email' => __('flash-messages.oidc.error.multi_tenant_not_allowed')]);

        $this->get(route('auth.oidc.callback', ['externalUserSource' => $source->id]))
            ->assertRedirect(route('login'));

        $this->assertFalse(Auth::check());
        $this->assertDatabaseMissing('users', ['email' => 'new.person@example.com']);
    }

    #[Test]
    public function a_custom_discovery_url_with_a_multi_tenant_segment_is_rejected(): void
    {
        $source = $this->oidcSource([
            'discovery_url' => 'https://login.microsoftonline.com/organizations/v2.0/.well-known/openid-configuration',
        ]);
        $this->fakeOidcLogin(['email_verified' => true]);

        $this->get(route('auth.oidc.callback', ['externalUserSource' => $source->id]))
            ->assertRedirect(route('login'));

        $this->assertDatabaseMissing('users', ['email' => 'new.person@example.com']);
    }

    #[Test]
    public function multi_tenant_endpoints_cannot_be_saved(): void
    {
        $this->actingAsAdmin();

        $this->postJson(route('tool.external-user-management.sources.store'), $this->oidcPayload([
            'provider_preset' => 'microsoft',
            'tenant_id' => 'Organizations',
        ]))->assertUnprocessable()->assertJsonValidationErrors('config.tenant_id');

        $this->postJson(route('tool.external-user-management.sources.store'), $this->oidcPayload([
            'provider_preset' => 'custom',
            'discovery_url' => 'https://login.microsoftonline.com/consumers/v2.0/.well-known/openid-configuration',
        ]))->assertUnprocessable()->assertJsonValidationErrors('config.discovery_url');

        $this->postJson(route('tool.external-user-management.sources.store'), $this->oidcPayload([
            'provider_preset' => 'microsoft',
            'tenant_id' => '11111111-2222-3333-4444-555555555555',
        ]))->assertSessionHasNoErrors();

        $this->assertSame(1, ExternalUserSource::query()->where('name', 'Neue Quelle')->count());
    }

    #[Test]
    public function new_sources_only_accept_immutable_identifier_claims_and_store_the_trust_flag(): void
    {
        $this->actingAsAdmin();

        $this->postJson(route('tool.external-user-management.sources.store'), $this->oidcPayload([
            'provider_preset' => 'custom',
            'discovery_url' => 'https://idp.example.com/.well-known/openid-configuration',
            'identifier_attribute' => 'email',
        ]))->assertUnprocessable()->assertJsonValidationErrors('config.identifier_attribute');

        $this->postJson(route('tool.external-user-management.sources.store'), $this->oidcPayload([
            'provider_preset' => 'custom',
            'discovery_url' => 'https://idp.example.com/.well-known/openid-configuration',
            'identifier_attribute' => 'oid',
        ]))->assertSessionHasNoErrors();

        $source = ExternalUserSource::query()->where('name', 'Neue Quelle')->firstOrFail();
        $this->assertSame('oid', $source->config['identifier_attribute']);
        $this->assertFalse($source->trustsIdpEmail());
    }

    #[Test]
    public function an_existing_legacy_identifier_may_stay_but_not_be_switched_to_another_mutable_claim(): void
    {
        $this->actingAsAdmin();
        $source = $this->oidcSource(['identifier_attribute' => 'email', 'trust_idp_email' => true]);
        $payload = fn (string $identifier): array => $this->oidcPayload([
            'provider_preset' => 'custom',
            'discovery_url' => 'https://idp.example.com/.well-known/openid-configuration',
            'identifier_attribute' => $identifier,
            'trust_idp_email' => true,
        ]);

        $this->putJson(
            route('tool.external-user-management.sources.update', ['externalUserSource' => $source->id]),
            $payload('email')
        )->assertSessionHasNoErrors();

        $this->putJson(
            route('tool.external-user-management.sources.update', ['externalUserSource' => $source->id]),
            $payload('preferred_username')
        )->assertUnprocessable()->assertJsonValidationErrors('config.identifier_attribute');

        $this->assertSame('email', $source->fresh()->config['identifier_attribute']);
        $this->assertTrue($source->fresh()->trustsIdpEmail());
    }

    #[Test]
    public function the_migration_trusts_existing_oidc_sources_only(): void
    {
        $oidc = $this->oidcSource();
        $ldap = ExternalUserSource::query()->create([
            'name' => 'LDAP',
            'active' => true,
            'type' => 'ldap',
            'config' => ['host' => 'ldap.example.test'],
        ]);

        $migration = require database_path('migrations/2026_10_20_180000_trust_idp_email_for_existing_oidc_sources.php');
        $migration->up();

        $this->assertTrue($oidc->fresh()->trustsIdpEmail());
        $this->assertSame('secret', $oidc->fresh()->config['client_secret']);
        $this->assertArrayNotHasKey('trust_idp_email', $ldap->fresh()->config);
    }

    /**
     * @param array<int, array<string, mixed>> $entries
     * @return array{total:int, synced:int, skipped:int}
     */
    private function syncLdap(ExternalUserSource $source, array $entries): array
    {
        $this->mock(MailService::class, function ($mock): void {
            $mock->shouldReceive('sendExternalUserImported');
        });
        $this->mock(LdapService::class, function ($mock) use ($entries): void {
            $mock->shouldReceive('fetchUsers')->andReturn(collect($entries));
        });

        return app(ExternalUserSyncService::class)->syncSource($source);
    }

    /**
     * @return array<string, mixed>
     */
    private function ldapEntry(string $identifier, string $email): array
    {
        return [
            'identifier' => $identifier,
            'email' => $email,
            'email_verified' => true,
            'first_name' => 'Lia',
            'last_name' => 'Licht',
            'groups' => [],
            'meta_data' => ['distinguished_name' => 'uid=' . $identifier . ',dc=example,dc=test'],
        ];
    }

    private function ldapSource(array $config = []): ExternalUserSource
    {
        return ExternalUserSource::query()->create([
            'name' => 'LDAP',
            'active' => true,
            'type' => 'ldap',
            'config' => array_merge(['host' => 'ldap.example.test'], $config),
        ]);
    }

    #[Test]
    public function the_ldap_sync_honours_the_domain_allowlist(): void
    {
        $source = $this->ldapSource(['allowed_domains' => ['theater.test']]);

        $result = $this->syncLdap($source, [
            $this->ldapEntry('inside', 'inside@theater.test'),
            $this->ldapEntry('outside', 'outside@gmail.test'),
        ]);

        $this->assertSame(['total' => 2, 'synced' => 1, 'skipped' => 1], $result);
        $this->assertDatabaseHas('users', ['email' => 'inside@theater.test']);
        $this->assertDatabaseMissing('users', ['email' => 'outside@gmail.test']);
    }

    #[Test]
    public function the_ldap_sync_without_allowlist_keeps_accepting_every_domain(): void
    {
        $source = $this->ldapSource();

        $result = $this->syncLdap($source, [$this->ldapEntry('anyone', 'anyone@gmail.test')]);

        $this->assertSame(1, $result['synced']);
        $this->assertDatabaseHas('users', ['email' => 'anyone@gmail.test']);
    }

    #[Test]
    public function a_person_deleted_in_artwork_is_not_recreated_by_the_sync(): void
    {
        $source = $this->ldapSource();
        $this->syncLdap($source, [$this->ldapEntry('lia', 'lia@theater.test')]);

        $user = User::query()->where('email', 'lia@theater.test')->firstOrFail();
        $this->assertNotNull(ExternalUser::query()->where('identification', 'lia')->value('user_id'));
        $user->delete();

        $result = $this->syncLdap($source, [$this->ldapEntry('lia', 'lia@theater.test')]);

        $this->assertSame(['total' => 1, 'synced' => 0, 'skipped' => 1], $result);
        $this->assertDatabaseMissing('users', ['email' => 'lia@theater.test']);
    }

    #[Test]
    public function a_deleted_person_can_be_reinvited_and_is_linked_again(): void
    {
        $source = $this->ldapSource();
        $this->syncLdap($source, [$this->ldapEntry('lia', 'lia@theater.test')]);
        User::query()->where('email', 'lia@theater.test')->firstOrFail()->delete();

        // Neu angelegtes lokales Konto (z. B. über eine Einladung) wird per E-Mail wieder verknüpft.
        $reinvited = User::factory()->create(['email' => 'lia@theater.test']);

        $result = $this->syncLdap($source, [$this->ldapEntry('lia', 'lia@theater.test')]);

        $this->assertSame(1, $result['synced']);
        $this->assertSame($reinvited->id, ExternalUser::query()->where('identification', 'lia')->value('user_id'));
    }
    // ---------- Audit 26.10.2026, H-4: Verknüpfung entzieht alle bisherigen Zugänge ----------

    /**
     * Lokales Konto mit Passwort, Datenbank-Sitzung und Passport-Tokens (App und Maschine) – so, wie
     * es jemand nach einem Pre-Claim der Verzeichnisadresse besäße.
     */
    private function localAccountWithAccess(string $email): User
    {
        if (!is_readable(Passport::keyPath('oauth-private.key'))) {
            $this->artisan('passport:keys');
        }
        app(ClientRepository::class)->createPersonalAccessGrantClient('Test Personal Access Client');

        $user = User::factory()->create(['email' => $email, 'password' => Hash::make('local-password')]);
        $user->forceFill(['remember_token' => 'old-remember-token'])->save();
        $user->createToken('Phone', ['app']);
        $user->createToken('Machine key', ['calendar:read']);

        config(['session.driver' => 'database']);
        DB::table('sessions')->insert([
            'id' => 'pre-claim-session',
            'user_id' => $user->id,
            'ip_address' => '127.0.0.1',
            'user_agent' => 'test',
            'payload' => '',
            'last_activity' => now()->timestamp,
        ]);

        return $user;
    }

    private function assertAllAccessRevoked(User $user): void
    {
        $user->refresh();
        $this->assertFalse(Hash::check('local-password', $user->password));
        $this->assertNotSame('old-remember-token', $user->remember_token);
        $this->assertDatabaseMissing('sessions', ['id' => 'pre-claim-session']);
        $this->assertSame(0, Token::query()->where('user_id', $user->id)->where('revoked', false)->count());
    }

    #[Test]
    public function ldap_sync_linking_an_existing_account_revokes_its_previous_access(): void
    {
        $user = $this->localAccountWithAccess('new.hire@theater.test');

        $this->syncLdap($this->ldapSource(), [$this->ldapEntry('new-hire', 'new.hire@theater.test')]);

        $this->assertSame('ldap', $user->fresh()->auth_provider);
        $this->assertAllAccessRevoked($user);
    }

    #[Test]
    public function oidc_login_linking_an_existing_account_revokes_its_previous_access(): void
    {
        $user = $this->localAccountWithAccess('new.person@example.com');
        $source = $this->oidcSource();
        $this->fakeOidcLogin(['email_verified' => true]);

        $this->get(route('auth.oidc.callback', ['externalUserSource' => $source->id]));

        $this->assertSame('oidc', $user->fresh()->auth_provider);
        $this->assertAllAccessRevoked($user);
        $this->assertTrue(Auth::check());
        $this->assertSame($user->id, Auth::id());
    }

    #[Test]
    public function resolving_an_already_linked_identity_keeps_access(): void
    {
        $source = $this->ldapSource();
        $this->syncLdap($source, [$this->ldapEntry('linked', 'linked@theater.test')]);
        $user = User::query()->where('email', 'linked@theater.test')->firstOrFail();
        $user->forceFill(['remember_token' => 'kept-token'])->save();

        $this->syncLdap($source, [$this->ldapEntry('linked', 'linked@theater.test')]);

        $this->assertSame('kept-token', $user->fresh()->remember_token);
    }

    // ---------- Audit 31.10.2026, F-05: Admin-Konten nie per mail-Attribut an LDAP binden ----------

    #[Test]
    public function the_ldap_sync_does_not_link_a_local_admin_by_email(): void
    {
        // Zwei lokale Admins: nicht der Schutz des letzten Admins, sondern die Admin-Regel greift.
        $this->adminUser();
        $victim = $this->adminUser(User::factory()->create(['email' => 'admin2@theater.test']));

        $result = $this->syncLdap($this->ldapSource(), [$this->ldapEntry('attacker', 'admin2@theater.test')]);

        $this->assertSame(['total' => 1, 'synced' => 0, 'skipped' => 1], $result);
        $this->assertSame('local', $victim->fresh()->auth_provider);
        $this->assertNull(ExternalUser::query()->where('identification', 'attacker')->value('user_id'));
    }

    #[Test]
    public function the_ldap_login_does_not_take_over_a_local_admin_by_email(): void
    {
        $this->adminUser();
        $victim = $this->adminUser(User::factory()->create([
            'email' => 'admin2@theater.test',
            'password' => Hash::make('local-password'),
        ]));
        $this->ldapSource();
        $this->mock(LdapService::class, function ($mock): void {
            $mock->shouldReceive('authenticateAndFetch')->andReturn(
                $this->ldapEntry('attacker', 'admin2@theater.test')
            );
        });

        $this->post(route('login'), ['email' => 'admin2@theater.test', 'password' => 'attacker-ldap-password']);

        $this->assertGuest();
        $victim->refresh();
        $this->assertSame('local', $victim->auth_provider);
        $this->assertTrue(Hash::check('local-password', $victim->password));
    }

    #[Test]
    public function the_ldap_sync_still_links_existing_non_admin_accounts_by_email(): void
    {
        $user = User::factory()->create(['email' => 'staff@theater.test']);

        $this->syncLdap($this->ldapSource(), [$this->ldapEntry('staff', 'staff@theater.test')]);

        $this->assertSame('ldap', $user->fresh()->auth_provider);
    }

    #[Test]
    public function a_local_admin_with_a_directory_entry_logs_in_with_the_local_password(): void
    {
        $this->adminUser();
        $admin = $this->adminUser(User::factory()->create([
            'email' => 'admin2@theater.test',
            'password' => Hash::make('local-password'),
        ]));
        $this->ldapSource();
        $this->mock(LdapService::class, function ($mock): void {
            $mock->shouldNotReceive('authenticateAndFetch');
        });

        $this->post(route('login'), ['email' => 'admin2@theater.test', 'password' => 'local-password']);

        $this->assertAuthenticatedAs($admin);
        $this->assertSame('local', $admin->fresh()->auth_provider);
    }

    #[Test]
    public function an_ldap_bound_admin_is_not_rekeyed_to_another_subject_by_email(): void
    {
        $source = $this->ldapSource();
        $admin = $this->adminUser(User::factory()->create(['email' => 'admin2@theater.test']));
        $admin->forceFill([
            'auth_provider' => 'ldap',
            'auth_provider_id' => 'admin-original',
            'auth_provider_issuer' => $source->resolvedIssuer(),
        ])->save();

        $result = $this->syncLdap($source, [$this->ldapEntry('attacker', 'admin2@theater.test')]);

        $this->assertSame(0, $result['synced']);
        $this->assertSame('admin-original', $admin->fresh()->auth_provider_id);
    }
}
