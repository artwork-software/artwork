<?php

namespace Tests\Feature;

use Artwork\Core\Services\InsecureDefaultsDetector;
use Artwork\Modules\GeneralSettings\Models\GeneralSettings;
use Artwork\Modules\GeneralSettings\Services\SetupTokenService;
use Artwork\Modules\Role\Enums\RoleEnum;
use Artwork\Modules\User\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\UserTestSeeder;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\Test;

/**
 * Audit 20.10.2026, Paket 1 „Auslieferung härten“: K1 Setup-Token, H1/H2/N1/N2 Default-Secrets,
 * Horizon-Gate, M9 leere .env-Zeilen, H2 nginx /apps, N5 Seeder-Schutz.
 */
final class SecurityAuditDeploymentRegressionTest extends FeatureTestCase
{
    private const STRONG_PASSWORD = 'Str0ng!Passw0rd#2026';

    private SetupTokenService $setupTokenService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->setupTokenService = app(SetupTokenService::class);
        $this->setupTokenService->clear();
        config(['security.setup_token' => null]);
    }

    protected function tearDown(): void
    {
        $this->setupTokenService->clear();

        parent::tearDown();
    }

    private function runInEnvironment(string $environment): void
    {
        $this->app->detectEnvironment(static fn (): string => $environment);
    }

    private function openSetup(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $settings = app(GeneralSettings::class);
        $settings->setup_finished = false;
        $settings->save();
    }

    /**
     * @return array<string, string>
     */
    private function setupPayload(string $email, ?string $token): array
    {
        return array_filter([
            'setup_token' => $token,
            'first_name' => 'Erste',
            'last_name' => 'Admin',
            'email' => $email,
            'position' => 'Leitung',
            'description' => 'x',
            'business' => 'Haus',
            'password' => self::STRONG_PASSWORD,
            'password_confirmation' => self::STRONG_PASSWORD,
        ], static fn (?string $value): bool => $value !== null);
    }

    // ---------------------------------------------------------------------------------------
    // K1 – Setup nur mit Einmal-Token
    // ---------------------------------------------------------------------------------------

    #[Test]
    public function setup_without_token_creates_no_admin(): void
    {
        $this->openSetup();
        $this->setupTokenService->regenerate();
        $email = 'intruder-' . Str::random(6) . '@example.test';

        $this->from(route('setup'))->post(route('setup.create'), $this->setupPayload($email, null))
            ->assertRedirect(route('setup'))
            ->assertSessionHasErrors('setup_token');

        $this->assertDatabaseMissing('users', ['email' => $email]);
        $this->assertFalse(app(GeneralSettings::class)->refresh()->setup_finished);
    }

    #[Test]
    public function setup_with_wrong_token_creates_no_admin(): void
    {
        $this->openSetup();
        $this->setupTokenService->regenerate();
        $email = 'intruder-' . Str::random(6) . '@example.test';

        $this->from(route('setup'))->post(route('setup.create'), $this->setupPayload($email, 'guessed'))
            ->assertSessionHasErrors('setup_token');

        $this->assertDatabaseMissing('users', ['email' => $email]);
    }

    #[Test]
    public function setup_is_closed_while_no_token_exists_at_all(): void
    {
        $this->openSetup();
        $email = 'intruder-' . Str::random(6) . '@example.test';

        $this->from(route('setup'))->post(route('setup.create'), $this->setupPayload($email, ''))
            ->assertSessionHasErrors('setup_token');

        $this->assertDatabaseMissing('users', ['email' => $email]);
    }

    #[Test]
    public function setup_with_token_from_env_creates_admin(): void
    {
        $this->openSetup();
        config(['security.setup_token' => 'env-setup-token-123']);
        $email = 'first-admin-' . Str::random(6) . '@example.test';

        $this->post(route('setup.create'), $this->setupPayload($email, 'env-setup-token-123'))
            ->assertRedirect();

        $user = User::query()->where('email', $email)->firstOrFail();
        $this->assertTrue($user->hasRole(RoleEnum::ARTWORK_ADMIN->value));
        $this->assertTrue(app(GeneralSettings::class)->refresh()->setup_finished);
    }

    #[Test]
    public function setup_page_logs_generated_token_once_and_that_token_works_once(): void
    {
        $this->openSetup();
        Log::spy();

        $this->get(route('setup'))->assertOk();
        $this->get(route('setup'))->assertOk();

        $setupUrl = null;
        Log::shouldHaveReceived('warning')
            ->once()
            ->withArgs(static function (string $message) use (&$setupUrl): bool {
                if (preg_match('~(https?://\S+/setup\?token=\S+)~', $message, $matches) === 1) {
                    $setupUrl = $matches[1];
                }

                return str_contains($message, 'setup token');
            });
        $this->assertNotNull($setupUrl);
        parse_str((string) parse_url($setupUrl, PHP_URL_QUERY), $query);
        $token = (string) $query['token'];

        $email = 'first-admin-' . Str::random(6) . '@example.test';
        $this->post(route('setup.create'), $this->setupPayload($email, $token))->assertRedirect();
        $this->assertDatabaseHas('users', ['email' => $email]);

        // Nach dem Setup ist der Token verbraucht.
        $this->assertFalse($this->setupTokenService->isValid($token));
    }

    #[Test]
    public function setup_page_prefills_token_from_url_without_validating_it(): void
    {
        $this->openSetup();
        $this->setupTokenService->regenerate();

        $this->get(route('setup', ['token' => 'from-url']))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Auth/Register')
                ->where('setupToken', 'from-url')
                ->where('setupTokenFromEnv', false));
    }

    #[Test]
    public function setup_token_command_prints_a_valid_setup_url(): void
    {
        $this->openSetup();

        $this->assertSame(0, Artisan::call('artwork:setup-token'));
        $output = Artisan::output();

        $this->assertSame(1, preg_match('~/setup\?token=([A-Za-z0-9]+)~', $output, $matches));
        $this->assertTrue($this->setupTokenService->isValid($matches[1]));
    }

    #[Test]
    public function setup_token_command_clears_token_after_setup(): void
    {
        $this->setupTokenService->regenerate();
        $settings = app(GeneralSettings::class);
        $settings->setup_finished = true;
        $settings->save();

        Artisan::call('artwork:setup-token');

        $this->assertFalse($this->setupTokenService->hasToken());
    }

    // ---------------------------------------------------------------------------------------
    // H1/H2/N1/N2 – Bekannte Default-Secrets in production
    // ---------------------------------------------------------------------------------------

    private function useStrongSecrets(): void
    {
        config([
            'app.key' => 'base64:' . base64_encode(random_bytes(32)),
            'reverb.apps.apps' => [['key' => 'k', 'secret' => Str::random(48), 'app_id' => 'artwork']],
        ]);
    }

    #[Test]
    public function production_with_publicly_known_app_key_blocks_web_requests(): void
    {
        $this->useStrongSecrets();
        config(['app.key' => InsecureDefaultsDetector::KNOWN_APP_KEYS[0]]);
        $this->runInEnvironment('production');

        $response = $this->get('/login');

        $response->assertStatus(503);
        $this->assertStringNotContainsString('APP_KEY', (string) $response->getContent());
    }

    #[Test]
    public function production_with_default_or_empty_reverb_secret_blocks_web_requests(): void
    {
        $this->runInEnvironment('production');

        foreach (['app-secret', 'set-a-key-here', ''] as $secret) {
            $this->useStrongSecrets();
            config(['reverb.apps.apps' => [['key' => 'k', 'secret' => $secret, 'app_id' => 'artwork']]]);

            $this->get('/login')->assertStatus(503);
        }
    }

    #[Test]
    public function production_with_own_secrets_serves_requests(): void
    {
        $this->useStrongSecrets();
        $this->runInEnvironment('production');

        $this->get('/login')->assertOk();
    }

    #[Test]
    public function weak_database_password_is_logged_once_but_does_not_block(): void
    {
        $this->useStrongSecrets();
        $connection = config('database.default');
        config(["database.connections.{$connection}.password" => 'password']);
        $this->runInEnvironment('production');
        Log::spy();

        $this->get('/login')->assertOk();
        $this->get('/login')->assertOk();

        Log::shouldHaveReceived('critical')
            ->once()
            ->withArgs(static fn (string $message, array $context): bool => in_array(
                'DB_PASSWORD is a publicly known default.',
                $context['findings'] ?? [],
                true
            ));
    }

    #[Test]
    public function outside_production_known_secrets_do_not_block(): void
    {
        config(['app.key' => InsecureDefaultsDetector::KNOWN_APP_KEYS[0]]);

        $this->get('/login')->assertOk();
    }

    #[Test]
    public function non_production_environment_on_public_url_is_detected(): void
    {
        $detector = app(InsecureDefaultsDetector::class);

        $this->runInEnvironment('local');
        config(['app.url' => 'https://artwork.theater.example']);
        $this->assertTrue($detector->runsNonProductionOnPublicUrl());

        foreach (['http://localhost', 'https://artwork.ddev.site', 'http://192.168.1.20', 'http://artwork.test'] as $url) {
            config(['app.url' => $url]);
            $this->assertFalse($detector->runsNonProductionOnPublicUrl(), $url);
        }

        $this->runInEnvironment('production');
        config(['app.url' => 'https://artwork.theater.example']);
        $this->assertFalse($detector->runsNonProductionOnPublicUrl());
    }

    // ---------------------------------------------------------------------------------------
    // H1 – Horizon nur für artwork-Admins (auch bei APP_ENV=local)
    // ---------------------------------------------------------------------------------------

    #[Test]
    public function horizon_is_closed_for_guests_even_in_local_environment(): void
    {
        $this->runInEnvironment('local');

        $this->get('/horizon')->assertForbidden();
        $this->getJson('/horizon/api/stats')->assertForbidden();
    }

    #[Test]
    public function horizon_is_closed_for_users_without_admin_role(): void
    {
        $this->runInEnvironment('local');
        $this->actingAs(User::factory()->create());

        $this->get('/horizon')->assertForbidden();
    }

    #[Test]
    public function horizon_gate_allows_only_artwork_admins(): void
    {
        $admin = $this->actingAsAdmin();

        $this->assertTrue(Gate::forUser($admin)->allows('viewHorizon'));
        $this->assertFalse(Gate::forUser(User::factory()->create())->allows('viewHorizon'));
        $this->get('/horizon')->assertOk();
    }

    // ---------------------------------------------------------------------------------------
    // M9 – Leere .env-Zeilen hebeln die Defaults nicht aus
    // ---------------------------------------------------------------------------------------

    /**
     * @param array<string, string> $env
     * @return array<string, mixed>
     */
    private function loadConfigWithEnv(string $file, array $env): array
    {
        $backup = [];
        foreach ($env as $name => $value) {
            $backup[$name] = [$_SERVER[$name] ?? null, $_ENV[$name] ?? null];
            $_SERVER[$name] = $_ENV[$name] = $value;
        }

        try {
            return require config_path($file);
        } finally {
            foreach ($backup as $name => [$server, $envValue]) {
                if ($server === null) {
                    unset($_SERVER[$name]);
                } else {
                    $_SERVER[$name] = $server;
                }
                if ($envValue === null) {
                    unset($_ENV[$name]);
                } else {
                    $_ENV[$name] = $envValue;
                }
            }
        }
    }

    #[Test]
    public function empty_trusted_proxies_line_keeps_private_network_default(): void
    {
        $config = $this->loadConfigWithEnv('app.php', ['TRUSTED_PROXIES' => '']);

        $this->assertStringContainsString('10.0.0.0/8', $config['trusted_proxies']);
    }

    #[Test]
    public function empty_session_secure_cookie_line_keeps_production_default(): void
    {
        $config = $this->loadConfigWithEnv('session.php', ['SESSION_SECURE_COOKIE' => '', 'APP_ENV' => 'production']);
        $this->assertTrue($config['secure']);

        $config = $this->loadConfigWithEnv(
            'session.php',
            ['SESSION_SECURE_COOKIE' => 'false', 'APP_ENV' => 'production']
        );
        $this->assertFalse($config['secure']);
    }

    // ---------------------------------------------------------------------------------------
    // Ausgelieferte Dateien: .env.example, nginx, Entrypoint
    // ---------------------------------------------------------------------------------------

    #[Test]
    public function env_example_ships_production_defaults_without_known_secrets(): void
    {
        $envExample = (string) file_get_contents(base_path('.env.example'));

        $this->assertMatchesRegularExpression('/^APP_ENV=production$/m', $envExample);
        $this->assertMatchesRegularExpression('/^APP_DEBUG=false$/m', $envExample);
        $this->assertMatchesRegularExpression('/^REVERB_APP_SECRET=$/m', $envExample);
        $this->assertStringNotContainsString('app-secret', $envExample);
        $this->assertDoesNotMatchRegularExpression('/^(TRUSTED_PROXIES|SESSION_SECURE_COOKIE)=\s*$/m', $envExample);
        $this->assertDoesNotMatchRegularExpression('/^DB_PASSWORD=password$/m', $envExample);
        $this->assertDoesNotMatchRegularExpression('/^MEILISEARCH_KEY=ddev$/m', $envExample);
    }

    #[Test]
    public function nginx_proxies_only_the_reverb_websocket_path(): void
    {
        $vhost = (string) file_get_contents(base_path('dockerfiles/artwork-php.84.vhost.conf'));

        $this->assertStringContainsString('location /app/ {', $vhost);
        $this->assertDoesNotMatchRegularExpression('~location\s+/app\s*\{~', $vhost);
        $this->assertDoesNotMatchRegularExpression('~location\s+[^{]*/apps~', $vhost);
    }

    #[Test]
    public function container_start_prints_the_setup_token(): void
    {
        $entrypoint = (string) file_get_contents(base_path('dockerfiles/entrypoint.sh'));

        $this->assertStringContainsString('php artisan artwork:setup-token', $entrypoint);
    }

    // ---------------------------------------------------------------------------------------
    // N5 – UserTestSeeder nur lokal
    // ---------------------------------------------------------------------------------------

    #[Test]
    public function user_test_seeder_does_nothing_in_production(): void
    {
        $this->runInEnvironment('production');
        $before = User::query()->count();

        // Direkt statt per db:seed, das in production eine Rückfrage stellt.
        app(UserTestSeeder::class)->run();

        $this->assertSame($before, User::query()->count());
    }
}
