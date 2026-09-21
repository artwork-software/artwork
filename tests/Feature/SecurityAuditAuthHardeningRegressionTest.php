<?php

namespace Tests\Feature;

use App\Actions\Fortify\PasswordValidationRules;
use Artwork\Modules\ExternalAccess\Models\ExternalAccess;
use Artwork\Modules\ExternalAccess\Models\ExternalLoginToken;
use Artwork\Modules\ExternalAccess\Settings\ExternalAccessSettings;
use Artwork\Modules\ExternalUserManagement\Api\LdapApi;
use Artwork\Modules\ExternalUserManagement\Models\ExternalUserSource;
use Artwork\Modules\ExternalUserManagement\Service\CredentialLoginService;
use Artwork\Modules\ExternalUserManagement\Service\OidcService;
use Artwork\Modules\GeneralSettings\Models\GeneralSettings;
use Artwork\Modules\User\Models\User;
use Database\Seeders\AuthUserSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use LdapRecord\Connection;
use Mockery;
use PHPUnit\Framework\Attributes\Test;

/**
 * Regressionstests zu den MITTEL-/NIEDRIG-Befunden aus Abschnitt D (Authentifizierung /
 * Session / Identität / Konfiguration) des Sicherheits-Audits vom 21.09.2026.
 */
final class SecurityAuditAuthHardeningRegressionTest extends FeatureTestCase
{
    private const STRONG_PASSWORD = 'Str0ng!Passw0rd#2026';

    private function runInProduction(): void
    {
        $this->app->detectEnvironment(static fn (): string => 'production');
    }

    // ---------------------------------------------------------------------------------------
    // D-MITTEL: Debug-Modus per DB-Flag (SetDeveloperEnvironment)
    // ---------------------------------------------------------------------------------------

    #[Test]
    public function developer_flag_does_not_enable_debug_mode_in_production(): void
    {
        $user = $this->actingAsAdmin();
        $user->forceFill(['is_developer' => true])->save();
        config(['app.debug' => false]);

        $this->runInProduction();
        $this->getJson(route('user.browser-sessions.index'))->assertOk();

        $this->assertFalse(config('app.debug'));
        $this->assertNotSame('local', config('app.env'));
    }

    #[Test]
    public function developer_flag_still_enables_debug_mode_outside_production(): void
    {
        $user = $this->actingAsAdmin();
        $user->forceFill(['is_developer' => true])->save();
        config(['app.debug' => false]);

        $this->getJson(route('user.browser-sessions.index'))->assertOk();

        $this->assertTrue(config('app.debug'));
    }

    // ---------------------------------------------------------------------------------------
    // D-MITTEL: „Passwort vergessen“ – keine Nutzer-Enumeration, Throttle
    // ---------------------------------------------------------------------------------------

    #[Test]
    public function forgot_password_answers_identically_for_known_and_unknown_email(): void
    {
        $user = User::factory()->create();

        $known = $this->from(route('password.request'))
            ->post(route('password.email'), ['email' => $user->email]);
        $unknown = $this->from(route('password.request'))
            ->post(route('password.email'), ['email' => 'nobody-' . Str::random(8) . '@example.test']);

        $known->assertRedirect(route('password.request'));
        $unknown->assertRedirect(route('password.request'));
        $unknown->assertSessionHasNoErrors();
        $known->assertSessionHasNoErrors();

        $this->assertSame($known->getStatusCode(), $unknown->getStatusCode());
        $this->assertSame($known->headers->get('Location'), $unknown->headers->get('Location'));
        $this->assertNotEmpty(session('status'));
        $this->assertStringNotContainsString('keine Person', (string) session('status'));
        $this->assertStringNotContainsString("can't find", (string) session('status'));
    }

    #[Test]
    public function forgot_password_json_answers_200_for_unknown_email(): void
    {
        $this->postJson(route('password.email'), ['email' => 'nobody-' . Str::random(8) . '@example.test'])
            ->assertOk()
            ->assertJsonStructure(['message']);
    }

    #[Test]
    public function forgot_password_is_throttled_after_five_requests(): void
    {
        $email = 'throttle-' . Str::random(8) . '@example.test';

        for ($i = 0; $i < 5; $i++) {
            $this->post(route('password.email'), ['email' => $email])->assertRedirect();
        }

        $this->post(route('password.email'), ['email' => $email])->assertStatus(429);
    }

    // ---------------------------------------------------------------------------------------
    // D-MITTEL: Demo-Admin mit bekanntem Passwort im Standard-Seeder / Demo-Commands
    // ---------------------------------------------------------------------------------------

    #[Test]
    public function auth_user_seeder_uses_random_passwords_only_in_production(): void
    {
        $seeder = $this->app->make(AuthUserSeeder::class);
        $method = new \ReflectionMethod($seeder, 'seedPassword');

        $this->assertSame('TestPass1234!$', $method->invoke($seeder, 'max.mustermann@artwork.software'));

        $this->runInProduction();
        $password = $method->invoke($seeder, 'max.mustermann@artwork.software');

        $this->assertSame(24, strlen($password));
        $this->assertNotSame('TestPass1234!$', $password);
        $this->assertNotSame($password, $method->invoke($seeder, 'lisa.musterfrau@artwork.software'));
    }

    #[Test]
    public function demo_commands_refuse_to_run_in_production(): void
    {
        $this->runInProduction();
        $usersBefore = User::count();

        $this->artisan('artwork:demo:base')
            ->expectsOutputToContain('Produktion')
            ->assertFailed();
        $this->artisan('artwork:demo:workers')->assertFailed();
        $this->artisan('artwork:demo:all')->assertFailed();

        $this->assertSame($usersBefore, User::count());
    }

    // ---------------------------------------------------------------------------------------
    // D-MITTEL: DB-/Meilisearch-Ports nur auf Loopback (docker-compose.yml)
    // ---------------------------------------------------------------------------------------

    #[Test]
    public function docker_compose_binds_db_and_meilisearch_ports_to_loopback(): void
    {
        $compose = file_get_contents(base_path('docker-compose.yml'));

        $this->assertStringContainsString('${DB_BIND_HOST:-127.0.0.1}:${FORWARD_DB_PORT:-3306}:3306', $compose);
        $this->assertStringContainsString(
            '${DB_BIND_HOST:-127.0.0.1}:${FORWARD_MEILISEARCH_PORT:-7700}:7700',
            $compose
        );
        $this->assertStringNotContainsString('MYSQL_ROOT_HOST', $compose);
    }

    // ---------------------------------------------------------------------------------------
    // D-NIEDRIG: „Andere Browser-Sitzungen abmelden“ über Web-Route statt totem api-Guard
    // ---------------------------------------------------------------------------------------

    private function seedSessionRows(User $user, User $other): void
    {
        $now = now()->getTimestamp();
        DB::table('sessions')->insert([
            ['id' => 'other-1', 'user_id' => $user->id, 'ip_address' => '10.0.0.1', 'user_agent' => 'Mozilla/5.0 (Windows NT 10.0) Chrome/120', 'payload' => '', 'last_activity' => $now],
            ['id' => 'other-2', 'user_id' => $user->id, 'ip_address' => '10.0.0.2', 'user_agent' => 'Mozilla/5.0 (iPhone) Safari/17', 'payload' => '', 'last_activity' => $now],
            ['id' => 'foreign-1', 'user_id' => $other->id, 'ip_address' => '10.0.0.3', 'user_agent' => 'Mozilla/5.0', 'payload' => '', 'last_activity' => $now],
        ]);
    }

    #[Test]
    public function logout_other_browser_sessions_rejects_wrong_password_with_422(): void
    {
        config(['session.driver' => 'database']);
        $user = User::factory()->create(['password' => Hash::make(self::STRONG_PASSWORD)]);
        $other = User::factory()->create();
        $this->seedSessionRows($user, $other);
        $this->actingAs($user);

        $this->deleteJson(route('user.browser-sessions.destroy'), ['password' => 'wrong-password'])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['password']);

        $this->assertDatabaseHas('sessions', ['id' => 'other-1']);
        $this->assertDatabaseHas('sessions', ['id' => 'other-2']);
    }

    #[Test]
    public function logout_other_browser_sessions_deletes_only_own_other_sessions(): void
    {
        config(['session.driver' => 'database']);
        $user = User::factory()->create(['password' => Hash::make(self::STRONG_PASSWORD)]);
        $other = User::factory()->create();
        $this->seedSessionRows($user, $other);
        $this->actingAs($user);

        $this->deleteJson(route('user.browser-sessions.destroy'), ['password' => self::STRONG_PASSWORD])
            ->assertOk();

        $this->assertDatabaseMissing('sessions', ['id' => 'other-1']);
        $this->assertDatabaseMissing('sessions', ['id' => 'other-2']);
        $this->assertDatabaseHas('sessions', ['id' => 'foreign-1']);
        $this->assertAuthenticatedAs($user);
    }

    #[Test]
    public function browser_sessions_index_lists_only_own_sessions(): void
    {
        config(['session.driver' => 'database']);
        $user = User::factory()->create(['password' => Hash::make(self::STRONG_PASSWORD)]);
        $other = User::factory()->create();
        $this->seedSessionRows($user, $other);
        $this->actingAs($user);

        $response = $this->getJson(route('user.browser-sessions.index'))->assertOk();
        $ips = collect($response->json('sessions'))->pluck('ip_address')->all();

        $this->assertContains('10.0.0.1', $ips);
        $this->assertContains('10.0.0.2', $ips);
        $this->assertNotContains('10.0.0.3', $ips);
    }

    #[Test]
    public function browser_session_routes_require_authentication(): void
    {
        $this->getJson(route('user.browser-sessions.index'))->assertUnauthorized();
        $this->deleteJson(route('user.browser-sessions.destroy'), ['password' => 'x'])->assertUnauthorized();
    }

    #[Test]
    public function jetstream_account_deletion_route_is_gone(): void
    {
        $this->assertFalse(\Illuminate\Support\Facades\Route::has('current-user.destroy'));
    }

    // ---------------------------------------------------------------------------------------
    // D-NIEDRIG: Timing-Enumeration beim Login (Dummy-Hash bei unbekannter E-Mail)
    // ---------------------------------------------------------------------------------------

    #[Test]
    public function credential_login_runs_hash_check_even_for_unknown_email(): void
    {
        Hash::shouldReceive('check')
            ->once()
            ->with('irrelevant-password', Mockery::pattern('/^\$2y\$/'))
            ->andReturn(false);

        $result = $this->app->make(CredentialLoginService::class)
            ->attempt('unknown-' . Str::random(8) . '@example.test', 'irrelevant-password');

        $this->assertNull($result);
    }

    #[Test]
    public function credential_login_still_authenticates_local_user(): void
    {
        $user = User::factory()->create(['password' => Hash::make(self::STRONG_PASSWORD)]);

        $result = $this->app->make(CredentialLoginService::class)->attempt($user->email, self::STRONG_PASSWORD);

        $this->assertNotNull($result);
        $this->assertSame($user->id, $result->id);
        $this->assertNull(
            $this->app->make(CredentialLoginService::class)->attempt($user->email, 'definitely-wrong')
        );
    }

    // ---------------------------------------------------------------------------------------
    // D-NIEDRIG: Setup-Race (Cache-Lock + erneute Prüfung)
    // ---------------------------------------------------------------------------------------

    private function setupPayload(string $email): array
    {
        return [
            'first_name' => 'Erste',
            'last_name' => 'Admin',
            'email' => $email,
            'position' => 'Leitung',
            'description' => 'x',
            'business' => 'Haus',
            'password' => self::STRONG_PASSWORD,
            'password_confirmation' => self::STRONG_PASSWORD,
        ];
    }

    #[Test]
    public function setup_creates_admin_once_and_forbids_a_second_attempt(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $settings = app(GeneralSettings::class);
        $settings->setup_finished = false;
        $settings->save();
        $email = 'first-admin-' . Str::random(6) . '@example.test';

        $this->post(route('setup.create'), $this->setupPayload($email))->assertRedirect();
        $this->assertTrue(app(GeneralSettings::class)->refresh()->setup_finished);
        $this->assertDatabaseHas('users', ['email' => $email]);

        Auth::guard('web')->logout();
        $before = User::count();
        $this->post(route('setup.create'), $this->setupPayload('second-' . Str::random(6) . '@example.test'))
            ->assertForbidden();
        $this->assertSame($before, User::count());
    }

    // ---------------------------------------------------------------------------------------
    // D-NIEDRIG: Reset-Link mit kodierter E-Mail
    // ---------------------------------------------------------------------------------------

    #[Test]
    public function password_reset_mail_url_encodes_the_email_address(): void
    {
        $user = User::factory()->create(['email' => 'plus+tag-' . Str::random(4) . '@example.test']);

        $mail = (new ResetPassword('token-123'))->toMail($user);
        $url = $mail->viewData['url'] ?? null;

        $this->assertIsString($url);
        $this->assertStringContainsString('email=' . urlencode($user->email), $url);
        $this->assertStringNotContainsString('email=plus+', $url);
    }

    // ---------------------------------------------------------------------------------------
    // D-NIEDRIG: Passwortregel (min 10, Buchstaben, Groß-/Klein, Ziffer; kein uncompromised)
    // ---------------------------------------------------------------------------------------

    private function passwordRulesUnderTest(): array
    {
        $holder = new class {
            use PasswordValidationRules;

            public function rules(): array
            {
                return $this->passwordRules();
            }
        };

        return $holder->rules();
    }

    #[Test]
    public function password_rules_reject_weak_and_accept_strong_passwords(): void
    {
        $rules = ['password' => $this->passwordRulesUnderTest()];

        foreach (['short1A', 'alllowercase1', 'ALLUPPERCASE1', 'NoDigitsHere!', '1234567890'] as $weak) {
            $this->assertTrue(
                Validator::make(['password' => $weak], $rules)->fails(),
                sprintf('"%s" hätte abgelehnt werden müssen', $weak)
            );
        }

        foreach ([self::STRONG_PASSWORD, 'TestPass1234!$', 'CorrectHorseBatteryStaple-9!', 'Abcdefghi1'] as $ok) {
            $this->assertTrue(
                Validator::make(['password' => $ok], $rules)->passes(),
                sprintf('"%s" hätte akzeptiert werden müssen', $ok)
            );
        }
    }

    #[Test]
    public function setup_rejects_password_without_digit_or_mixed_case(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $settings = app(GeneralSettings::class);
        $settings->setup_finished = false;
        $settings->save();

        $payload = $this->setupPayload('weak-' . Str::random(6) . '@example.test');
        $payload['password'] = $payload['password_confirmation'] = 'nodigitsnocaps';

        $this->from(route('setup'))->post(route('setup.create'), $payload)
            ->assertRedirect(route('setup'))
            ->assertSessionHasErrors('password');
        $this->assertDatabaseMissing('users', ['email' => $payload['email']]);
    }

    // ---------------------------------------------------------------------------------------
    // D-NIEDRIG: OIDC-Login ohne Remember-Me-Cookie
    // ---------------------------------------------------------------------------------------

    #[Test]
    public function oidc_login_does_not_set_a_remember_cookie(): void
    {
        $source = ExternalUserSource::query()->create([
            'name' => 'IdP',
            'active' => true,
            'type' => 'identity_provider',
            'config' => [
                'discovery_url' => 'https://idp.example.com/.well-known/openid-configuration',
                'client_id' => 'artwork',
                'client_secret' => 'secret',
                'allowed_domains' => ['example.com'],
            ],
        ]);
        $this->mock(OidcService::class, function ($mock): void {
            $mock->shouldReceive('userFromCallback')->andReturn([
                'identifier' => 'subject-' . Str::random(6),
                'email' => 'oidc.' . Str::random(6) . '@example.com',
                'first_name' => 'Oidc',
                'last_name' => 'User',
                'groups' => [],
                'email_verified' => true,
                'meta_data' => [],
            ]);
        });

        $response = $this->get(route('auth.oidc.callback', ['externalUserSource' => $source->id]));

        $response->assertRedirect();
        $this->assertTrue(Auth::check());
        $response->assertCookieMissing(Auth::guard('web')->getRecallerName());
    }

    // ---------------------------------------------------------------------------------------
    // D-NIEDRIG: LDAP-Default StartTLS
    // ---------------------------------------------------------------------------------------

    #[Test]
    public function ldap_connection_defaults_to_starttls_when_not_configured(): void
    {
        $source = new ExternalUserSource([
            'name' => 'Directory',
            'type' => 'ldap',
            'config' => ['host' => 'ldap://ad.example.test', 'base_dn' => 'dc=example,dc=test'],
        ]);
        $method = new \ReflectionMethod(LdapApi::class, 'createConnection');

        /** @var Connection $connection */
        $connection = $method->invoke($this->app->make(LdapApi::class), $source);

        $this->assertTrue($connection->getConfiguration()->get('use_tls'));

        $source->config = array_merge($source->config, ['use_tls' => false]);
        $connection = $method->invoke($this->app->make(LdapApi::class), $source);
        $this->assertFalse($connection->getConfiguration()->get('use_tls'));
    }

    // ---------------------------------------------------------------------------------------
    // D-NIEDRIG: Magic-Link-Einlösung per POST, GET zeigt Bestätigungsseite
    // ---------------------------------------------------------------------------------------

    #[Test]
    public function magic_link_get_does_not_consume_token_and_post_redeems_it(): void
    {
        $settings = app(ExternalAccessSettings::class);
        $settings->enabled = true;
        $settings->save();

        $external = ExternalAccess::factory()->active()->create();
        $plain = Str::random(64);
        $token = ExternalLoginToken::factory()->create([
            'external_access_id' => $external->id,
            'token_hash' => hash('sha256', $plain),
            'expires_at' => now()->addMinutes(15),
        ]);

        $this->get(route('external.login.redeem', ['token' => $plain]))->assertOk();
        $this->assertNull($token->fresh()->used_at);
        $this->assertFalse(Auth::guard('external')->check());

        $this->post(route('external.login.redeem.store', ['token' => $plain]))
            ->assertRedirect(route('external.dashboard'));
        $this->assertNotNull($token->fresh()->used_at);
        $this->assertSame($external->id, Auth::guard('external')->id());
    }

    // ---------------------------------------------------------------------------------------
    // D-NIEDRIG: kein APP_KEY in .env.testing (Test-Key liegt in phpunit.xml)
    // ---------------------------------------------------------------------------------------

    #[Test]
    public function env_testing_contains_no_app_key_value(): void
    {
        $envTesting = file_get_contents(base_path('.env.testing'));
        $phpunit = file_get_contents(base_path('phpunit.xml'));

        $this->assertMatchesRegularExpression('/^APP_KEY=\s*$/m', $envTesting);
        $this->assertDoesNotMatchRegularExpression('/^APP_KEY=base64:/m', $envTesting);
        $this->assertStringContainsString('<env name="APP_KEY" value="base64:', $phpunit);
    }
}
