<?php

namespace Tests\Feature\AppApi;

use Artwork\Modules\ModuleSettings\Models\ModuleSettings;
use Artwork\Modules\Permission\Enums\PermissionEnum;
use Artwork\Modules\User\Models\User;
use Laravel\Passport\ClientRepository;
use Laravel\Passport\Passport;
use Laravel\Passport\Token;
use Laravel\Fortify\Contracts\TwoFactorAuthenticationProvider;
use Laravel\Fortify\Fortify;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class AppAuthTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        if (!is_readable(Passport::keyPath('oauth-private.key'))) {
            $this->artisan('passport:keys');
        }

        // Schema-aware (this app still runs Passport's legacy oauth_clients columns),
        // matching how UpdateArtwork provisions the client in production.
        app(ClientRepository::class)->createPersonalAccessGrantClient('Test Personal Access Client');
    }

    private function login(array $overrides = []): \Illuminate\Testing\TestResponse
    {
        return $this->postJson(route('app.v1.auth.login'), array_merge([
            'email' => 'app-user@example.com',
            'password' => 'password',
            'device_name' => 'Test iPhone',
        ], $overrides));
    }

    private function createUser(): User
    {
        return User::factory()->create(['email' => 'app-user@example.com']);
    }

    #[Test]
    public function loginWithValidCredentialsReturnsScopedTokenThatWorksAgainstMe(): void
    {
        $user = $this->createUser();

        $response = $this->login();

        $response->assertOk()->assertJson([
            'user' => [
                'id' => $user->id,
                'name' => $user->full_name,
                'email' => 'app-user@example.com',
            ],
        ]);

        $token = $response->json('token');
        $this->assertIsString($token);
        $this->assertSame(['app'], Token::query()->sole()->scopes);

        $this->getJson(route('app.v1.me'), ['Authorization' => 'Bearer ' . $token])
            ->assertOk()
            ->assertJsonPath('user.id', $user->id)
            ->assertJsonPath('user.name', $user->full_name)
            ->assertJsonPath('user.email', 'app-user@example.com');
    }

    #[Test]
    public function loginWithWrongPasswordReturns401WithoutCreatingAToken(): void
    {
        $this->createUser();

        $this->login(['password' => 'wrong-password'])
            ->assertUnauthorized()
            ->assertJsonStructure(['message']);

        $this->assertSame(0, Token::query()->count());
    }

    #[Test]
    public function loginWithUnknownEmailReturnsSameMessageAsWrongPassword(): void
    {
        $this->createUser();

        $unknownEmail = $this->login(['email' => 'nobody@example.com'])->assertUnauthorized();
        $wrongPassword = $this->login(['password' => 'wrong-password'])->assertUnauthorized();

        $this->assertSame($wrongPassword->json('message'), $unknownEmail->json('message'));
        $this->assertSame(0, Token::query()->count());
    }

    #[Test]
    public function loginRefusesOidcAccountsEvenWithACorrectLocalPassword(): void
    {
        $this->createUser()->forceFill(['auth_provider' => 'oidc'])->save();

        // Gleiche Antwort wie jeder andere Fehlversuch – sonst ließe sich ermitteln, welche
        // Adressen als SSO-Konto existieren
        $oidcAnswer = $this->login()->assertUnauthorized();
        $unknownEmail = $this->login(['email' => 'nobody@example.com'])->assertUnauthorized();

        $this->assertSame($unknownEmail->json(), $oidcAnswer->json());
        $this->assertSame(0, Token::query()->count());
    }

    #[Test]
    public function loginWithAnArrayAsEmailIsAValidationErrorNotAServerError(): void
    {
        $this->postJson(route('app.v1.auth.login'), [
            'email' => ['app-user@example.com'],
            'password' => 'password',
            'device_name' => 'Test iPhone',
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('email');
    }

    #[Test]
    public function meReportsTheShiftPlanOnlyWithTheRightsOfTheShiftPlanEndpoints(): void
    {
        $roster = $this->actingAsApiUserWith(PermissionEnum::CAN_VIEW_OWN_ROSTER->value);
        $this->getJson(route('app.v1.me'))->assertOk()->assertJsonPath('user.permissions.can_view_shift_plan', true);
        $this->getJson(route('app.v1.shift-plan'))->assertOk();

        // Dienstplan-Ansicht (Planung) ohne eigenen Einsatzplan: der App-Dienstplan antwortet 403
        $this->actingAsApiUserWith(PermissionEnum::VIEW_SHIFT_PLAN->value);
        $this->getJson(route('app.v1.me'))->assertOk()->assertJsonPath('user.permissions.can_view_shift_plan', false);
        $this->getJson(route('app.v1.shift-plan'))->assertForbidden();

        $modules = app(ModuleSettings::class);
        $modules->shift_plan = false;
        $modules->save();
        Passport::actingAs($roster, ['app']);
        $this->getJson(route('app.v1.me'))->assertOk()->assertJsonPath('user.permissions.can_view_shift_plan', false);
    }

    #[Test]
    public function loginWithMissingFieldsReturns422(): void
    {
        $this->postJson(route('app.v1.auth.login'), [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['email', 'password', 'device_name']);
    }

    #[Test]
    public function meWithoutTokenReturns401(): void
    {
        $this->getJson(route('app.v1.me'))->assertUnauthorized();
    }

    #[Test]
    public function logoutRevokesTheTokenSoItNoLongerWorks(): void
    {
        $this->createUser();

        $token = $this->login()->json('token');
        $headers = ['Authorization' => 'Bearer ' . $token];

        $this->postJson(route('app.v1.auth.logout'), [], $headers)->assertNoContent();

        $this->assertSame(1, Token::query()->where('revoked', true)->count());

        // The guard caches the resolved user between in-test requests; a real client's
        // next request hits a fresh guard.
        $this->app['auth']->forgetGuards();
        $this->getJson(route('app.v1.me'), $headers)->assertUnauthorized();
    }

    #[Test]
    public function loginRequiresTwoFactorCodeBeforeIssuingAToken(): void
    {
        $user = $this->createUser();
        $user->forceFill([
            'two_factor_secret' => Fortify::currentEncrypter()->encrypt('test-secret'),
            'two_factor_recovery_codes' => Fortify::currentEncrypter()->encrypt(
                json_encode(['recovery-code'], JSON_THROW_ON_ERROR),
            ),
        ])->save();

        $this->login()
            ->assertUnprocessable()
            ->assertJsonPath('two_factor_required', true)
            ->assertJsonValidationErrors(['code']);

        $this->assertSame(0, Token::query()->count());
    }

    #[Test]
    public function loginAcceptsAValidTwoFactorCode(): void
    {
        $user = $this->createUser();
        $user->forceFill([
            'two_factor_secret' => Fortify::currentEncrypter()->encrypt('test-secret'),
            'two_factor_recovery_codes' => Fortify::currentEncrypter()->encrypt(
                json_encode(['recovery-code'], JSON_THROW_ON_ERROR),
            ),
        ])->save();

        $this->mock(
            TwoFactorAuthenticationProvider::class,
            static function (MockInterface $mock): void {
                $mock->shouldReceive('verify')
                    ->once()
                    ->with('test-secret', '123456')
                    ->andReturnTrue();
            },
        );

        $this->login(['code' => '123456'])->assertOk();

        $this->assertSame(1, Token::query()->count());
    }

    #[Test]
    public function loginAcceptsAndRotatesAValidRecoveryCode(): void
    {
        $user = $this->createUser();
        $user->forceFill([
            'two_factor_secret' => Fortify::currentEncrypter()->encrypt('test-secret'),
            'two_factor_recovery_codes' => Fortify::currentEncrypter()->encrypt(
                json_encode(['recovery-code'], JSON_THROW_ON_ERROR),
            ),
        ])->save();

        $this->login(['recovery_code' => 'recovery-code'])->assertOk();

        $this->assertNotContains('recovery-code', $user->refresh()->recoveryCodes());
        $this->assertSame(1, Token::query()->count());
    }

    #[Test]
    public function loginThrottleIsSegmentedByEmailAndIp(): void
    {
        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->login(['email' => 'missing@example.com'])->assertUnauthorized();
        }

        $this->login(['email' => 'missing@example.com'])->assertTooManyRequests();

        $this->withServerVariables(['REMOTE_ADDR' => '192.0.2.10'])
            ->login(['email' => 'missing@example.com'])
            ->assertUnauthorized();
    }
}
