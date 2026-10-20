<?php

namespace Tests\Feature;

use App\Actions\Fortify\ResetUserPassword;
use App\Actions\Fortify\UpdateUserPassword;
use App\Actions\Fortify\UpdateUserProfileInformation;
use Artwork\Modules\AppApi\Services\AppTokenService;
use Artwork\Modules\Mail\Settings\MailSettings;
use Artwork\Modules\Permission\Enums\PermissionEnum;
use Artwork\Modules\SageApiSettings\Models\SageApiSettings;
use Artwork\Modules\User\Models\User;
use Artwork\Modules\User\Notifications\EmailAddressChangedNotification;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Laravel\Passport\ClientRepository;
use Laravel\Passport\Passport;
use Laravel\Passport\Token;
use PHPUnit\Framework\Attributes\Test;

/**
 * Audit 20.10.2026, Paket 2 „Admin-Übernahme-Ketten“: H3 Mail-Einstellungen, M3 E-Mail-Wechsel und
 * Platzhalter gelöschter Nutzer:innen, M6 Sage-Zugangsdaten, M7 App-Gerätetokens.
 */
final class SecurityAuditTakeoverChainsRegressionTest extends FeatureTestCase
{
    private const PASSWORD = 'Str0ng!Passw0rd#2026';

    // ---------------------------------------------------------------------------------------
    // H3 – Mail-Einstellungen nur für artwork-Admins
    // ---------------------------------------------------------------------------------------

    /**
     * @return array<string, mixed>
     */
    private function mailPayload(array $overrides = []): array
    {
        return array_merge([
            'host' => 'smtp.theater.example',
            'port' => 587,
            'encryption' => 'tls',
            'username' => 'mailer@theater.example',
            'password' => '',
            'from_address' => 'noreply@theater.example',
            'from_name' => 'Artwork',
        ], $overrides);
    }

    private function storeMailSettings(string $host, ?string $username, ?string $password): void
    {
        $settings = app(MailSettings::class);
        $settings->host = $host;
        $settings->username = $username;
        $settings->password = $password;
        $settings->save();
    }

    #[Test]
    public function tool_settings_permission_no_longer_allows_redirecting_smtp(): void
    {
        $this->storeMailSettings('smtp.theater.example', 'mailer@theater.example', 'stored-secret');
        $this->actingAsUserWith(PermissionEnum::SETTINGS_UPDATE->value);

        $this->patch(route('tool.mail.update'), $this->mailPayload(['host' => 'smtp.attacker.example']))
            ->assertForbidden();
        $this->postJson(route('tool.mail.test'), ['recipient' => 'x@attacker.example'])
            ->assertForbidden();

        $this->assertSame('smtp.theater.example', app(MailSettings::class)->refresh()->host);
    }

    #[Test]
    public function tool_settings_permission_still_sees_the_page_read_only(): void
    {
        $this->actingAsUserWith(PermissionEnum::SETTINGS_UPDATE->value);

        $response = $this->get(route('tool.mail'))->assertOk();
        $this->assertInertiaWhere($response, 'mailSettings.can_manage', false);
    }

    #[Test]
    public function admin_changing_the_host_must_enter_the_password_again(): void
    {
        $this->storeMailSettings('smtp.theater.example', 'mailer@theater.example', 'stored-secret');
        $this->actingAsAdmin();

        $this->patch(route('tool.mail.update'), $this->mailPayload(['host' => 'smtp.other.example']))
            ->assertSessionHasErrors('password');

        $settings = app(MailSettings::class)->refresh();
        $this->assertSame('smtp.theater.example', $settings->host);
        $this->assertSame('stored-secret', $settings->password);

        $this->patch(route('tool.mail.update'), $this->mailPayload([
            'host' => 'smtp.other.example',
            'password' => 'new-secret',
        ]))->assertSessionHasNoErrors();

        $settings->refresh();
        $this->assertSame('smtp.other.example', $settings->host);
        $this->assertSame('new-secret', $settings->password);
    }

    #[Test]
    public function host_change_without_username_discards_the_stored_password(): void
    {
        $this->storeMailSettings('smtp.theater.example', null, 'stored-secret');
        $this->actingAsAdmin();

        $this->patch(route('tool.mail.update'), $this->mailPayload([
            'host' => 'relay.theater.example',
            'username' => '',
        ]))->assertSessionHasNoErrors();

        $settings = app(MailSettings::class)->refresh();
        $this->assertSame('relay.theater.example', $settings->host);
        $this->assertNull($settings->password);
    }

    #[Test]
    public function same_host_keeps_the_stored_password(): void
    {
        $this->storeMailSettings('smtp.theater.example', 'mailer@theater.example', 'stored-secret');
        $this->actingAsAdmin();

        $this->patch(route('tool.mail.update'), $this->mailPayload(['host' => 'SMTP.theater.example']))
            ->assertSessionHasNoErrors();

        $this->assertSame('stored-secret', app(MailSettings::class)->refresh()->password);
    }

    // ---------------------------------------------------------------------------------------
    // M3 – Eigene E-Mail nur mit Passwort, gültig, eindeutig, nicht @artwork.local
    // ---------------------------------------------------------------------------------------

    private function userWithPassword(array $attributes = []): User
    {
        return User::factory()->create(array_merge(['password' => Hash::make(self::PASSWORD)], $attributes));
    }

    /**
     * @return array<string, mixed>
     */
    private function detailsPayload(User $user, array $overrides = []): array
    {
        return array_merge([
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
            'email' => $user->email,
        ], $overrides);
    }

    #[Test]
    public function own_email_change_without_current_password_is_rejected(): void
    {
        $user = $this->userWithPassword();
        $this->actingAs($user);
        $newEmail = 'new-' . Str::random(6) . '@theater.example';

        $this->patch(route('user.update', $user), $this->detailsPayload($user, ['email' => $newEmail]))
            ->assertSessionHasErrors('current_password');
        $this->patch(route('user.update', $user), $this->detailsPayload($user, [
            'email' => $newEmail,
            'current_password' => 'wrong-password',
        ]))->assertSessionHasErrors('current_password');

        $this->assertNotSame($newEmail, $user->fresh()->email);
        Notification::assertNothingSent();
    }

    #[Test]
    public function own_email_change_with_current_password_succeeds_and_warns_the_old_address(): void
    {
        $user = $this->userWithPassword();
        $oldEmail = $user->email;
        $this->actingAs($user);
        $newEmail = 'new-' . Str::random(6) . '@theater.example';

        $this->patch(route('user.update', $user), $this->detailsPayload($user, [
            'email' => $newEmail,
            'current_password' => self::PASSWORD,
        ]))->assertSessionHasNoErrors();

        $this->assertSame($newEmail, $user->fresh()->email);
        Notification::assertSentTo(
            new AnonymousNotifiable(),
            EmailAddressChangedNotification::class,
            static fn ($notification, array $channels, AnonymousNotifiable $notifiable): bool =>
                $notifiable->routeNotificationFor('mail') === $oldEmail
        );
    }

    #[Test]
    public function email_must_be_valid_unique_and_not_on_the_internal_domain(): void
    {
        $user = $this->userWithPassword();
        $other = User::factory()->create();
        $this->actingAs($user);

        foreach (['not-an-email', $other->email, 'deleted-user@artwork.local', 'x@sub.artwork.local'] as $email) {
            $this->patch(route('user.update', $user), $this->detailsPayload($user, [
                'email' => $email,
                'current_password' => self::PASSWORD,
            ]))->assertSessionHasErrors('email');
        }

        $this->assertNotSame('deleted-user@artwork.local', $user->fresh()->email);
    }

    #[Test]
    public function idp_bound_account_cannot_change_its_own_email(): void
    {
        $user = $this->userWithPassword(['auth_provider' => 'oidc']);
        $this->actingAs($user);

        $this->patch(route('user.update', $user), $this->detailsPayload($user, [
            'email' => 'elsewhere-' . Str::random(6) . '@theater.example',
            'current_password' => self::PASSWORD,
        ]))->assertSessionHasErrors('email');
    }

    #[Test]
    public function saving_other_fields_without_email_change_needs_no_password(): void
    {
        $user = $this->userWithPassword();
        $this->actingAs($user);

        $this->patch(route('user.update', $user), $this->detailsPayload($user, ['position' => 'Technik']))
            ->assertSessionHasNoErrors();

        $this->assertSame('Technik', $user->fresh()->position);
    }

    #[Test]
    public function admin_changes_another_users_email_without_their_password(): void
    {
        $this->actingAsAdmin();
        $target = $this->userWithPassword();
        $newEmail = 'moved-' . Str::random(6) . '@theater.example';

        $this->patch(route('user.update', $target), $this->detailsPayload($target, ['email' => $newEmail]))
            ->assertSessionHasNoErrors();

        $this->assertSame($newEmail, $target->fresh()->email);
    }

    #[Test]
    public function fortify_profile_update_requires_current_password_for_email_change(): void
    {
        $user = $this->userWithPassword();
        $action = app(UpdateUserProfileInformation::class);
        $input = [
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
            'email' => 'fortify-' . Str::random(6) . '@theater.example',
        ];

        try {
            $action->update($user, $input);
            $this->fail('Email change without current password must fail.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('current_password', $exception->errors());
        }

        $action->update($user, $input + ['current_password' => self::PASSWORD]);
        $this->assertSame($input['email'], $user->fresh()->email);

        $this->expectException(ValidationException::class);
        $action->update($user, array_merge($input, [
            'email' => 'deleted-user@artwork.local',
            'current_password' => self::PASSWORD,
        ]));
    }

    #[Test]
    public function account_with_placeholder_email_does_not_inherit_data_of_deleted_users(): void
    {
        $admin = $this->actingAsAdmin();
        // Altbestand: jemand hat sich die Platzhalter-Adresse gegeben, bevor die Domain gesperrt war.
        $hijacker = User::factory()->create(['email' => 'deleted-user@artwork.local']);
        $leaving = User::factory()->create();

        $this->delete(route('user.destroy', $leaving))->assertRedirect();

        $placeholder = User::query()->where('is_deleted_placeholder', true)->sole();
        $this->assertNotSame($hijacker->id, $placeholder->id);
        $this->assertNotSame($admin->id, $placeholder->id);
        $this->assertStringEndsWith('@artwork.local', $placeholder->email);
        $this->assertFalse($hijacker->fresh()->is_deleted_placeholder);

        // Der Platzhalter wird über das Flag gefunden und wiederverwendet.
        $this->delete(route('user.destroy', User::factory()->create()))->assertRedirect();
        $this->assertSame(1, User::query()->where('is_deleted_placeholder', true)->count());
    }

    #[Test]
    public function placeholder_flag_hides_the_placeholder_but_not_accounts_with_its_email(): void
    {
        $placeholder = User::factory()->create(['is_deleted_placeholder' => true]);
        $impostor = User::factory()->create(['email' => 'deleted-user@artwork.local']);

        $visibleIds = User::query()->excludeDeletedPlaceholder()->pluck('id');

        $this->assertNotContains($placeholder->id, $visibleIds);
        $this->assertContains($impostor->id, $visibleIds);
        $this->assertFalse($placeholder->shouldBeSearchable());
    }

    #[Test]
    public function migration_flags_only_the_system_placeholder(): void
    {
        $system = User::factory()->create([
            'first_name' => 'Deleted',
            'last_name' => 'user',
            'email' => 'deleted-user@artwork.local',
        ]);
        DB::table('users')->where('id', $system->id)->update(['is_deleted_placeholder' => false]);

        $migration = require database_path('migrations/2026_10_20_130000_add_is_deleted_placeholder_to_users_table.php');
        $migration->up();

        $this->assertTrue($system->fresh()->is_deleted_placeholder);
    }

    // ---------------------------------------------------------------------------------------
    // M6 – Sage: nur https, Passwort bei Hostwechsel neu, verschlüsselt gespeichert
    // ---------------------------------------------------------------------------------------

    /**
     * @return array<string, mixed>
     */
    private function sagePayload(array $overrides = []): array
    {
        return array_merge([
            'host' => 'https://sage.theater.example',
            'endpoint' => '/api/v1/bookings',
            'user' => 'artwork',
            'password' => '',
            'enabled' => true,
        ], $overrides);
    }

    #[Test]
    public function sage_password_is_stored_encrypted(): void
    {
        SageApiSettings::query()->create($this->sagePayload(['password' => 'top-secret']));

        $raw = DB::table('sage_api_settings')->value('password');

        $this->assertNotSame('top-secret', $raw);
        $this->assertSame('top-secret', SageApiSettings::query()->first()->password);
    }

    #[Test]
    public function sage_host_change_requires_the_password_again(): void
    {
        SageApiSettings::query()->create($this->sagePayload(['password' => 'top-secret']));
        $this->actingAsAdmin();

        $this->post(route('tool.interfaces.sage.update'), $this->sagePayload([
            'host' => 'https://sage.attacker.example',
        ]))->assertSessionHasErrors('password');

        $stored = SageApiSettings::query()->first();
        $this->assertSame('https://sage.theater.example', $stored->host);
        $this->assertSame('top-secret', $stored->password);
    }

    #[Test]
    public function sage_host_must_use_https(): void
    {
        $this->actingAsAdmin();

        $this->post(route('tool.interfaces.sage.update'), $this->sagePayload([
            'host' => 'http://sage.theater.example',
            'password' => 'secret',
        ]))->assertSessionHasErrors('host');

        $this->assertDatabaseCount('sage_api_settings', 0);
    }

    #[Test]
    public function sage_migration_encrypts_plaintext_passwords_once(): void
    {
        $id = DB::table('sage_api_settings')->insertGetId([
            'host' => 'https://sage.theater.example',
            'endpoint' => '/api',
            'user' => 'artwork',
            'password' => 'plain-secret',
            'enabled' => true,
        ]);

        $migration = require database_path('migrations/2026_10_20_130100_encrypt_sage_api_settings_password.php');
        $migration->up();
        $migration->up();

        $this->assertNotSame('plain-secret', DB::table('sage_api_settings')->where('id', $id)->value('password'));
        $this->assertSame('plain-secret', SageApiSettings::query()->findOrFail($id)->password);
    }

    // ---------------------------------------------------------------------------------------
    // M7 – App-Gerätetokens: 90 Tage, Widerruf bei Passwortwechsel/-reset
    // ---------------------------------------------------------------------------------------

    private function preparePassport(): void
    {
        if (!is_readable(Passport::keyPath('oauth-private.key'))) {
            $this->artisan('passport:keys');
        }

        app(ClientRepository::class)->createPersonalAccessGrantClient('Test Personal Access Client');
    }

    #[Test]
    public function app_tokens_expire_after_90_days(): void
    {
        $this->preparePassport();
        $user = $this->userWithPassword(['email' => 'app-' . Str::random(6) . '@theater.example']);

        $this->postJson(route('app.v1.auth.login'), [
            'email' => $user->email,
            'password' => self::PASSWORD,
            'device_name' => 'Test iPhone',
        ])->assertOk();

        $token = Token::query()->where('user_id', $user->id)->sole();
        $this->assertTrue($token->expires_at->between(now()->addDays(89), now()->addDays(91)));
    }

    #[Test]
    public function password_change_revokes_app_tokens_but_not_machine_tokens(): void
    {
        $this->preparePassport();
        $user = $this->userWithPassword();
        $plainAppToken = app(AppTokenService::class)->issue($user, 'Phone');
        $user->createToken('Calendar integration', ['calendar:read']);

        $this->getJson(route('app.v1.me'), ['Authorization' => 'Bearer ' . $plainAppToken])->assertOk();

        app(UpdateUserPassword::class)->update($user, [
            'current_password' => self::PASSWORD,
            'password' => 'An0ther!Passw0rd#2026',
            'password_confirmation' => 'An0ther!Passw0rd#2026',
        ]);

        $tokens = Token::query()->where('user_id', $user->id)->get()->keyBy('name');
        $this->assertTrue($tokens['Phone']->revoked);
        $this->assertFalse($tokens['Calendar integration']->revoked);

        $this->app['auth']->forgetGuards();
        $this->getJson(route('app.v1.me'), ['Authorization' => 'Bearer ' . $plainAppToken])->assertUnauthorized();
    }

    #[Test]
    public function password_reset_revokes_app_tokens(): void
    {
        $this->preparePassport();
        $user = $this->userWithPassword();
        app(AppTokenService::class)->issue($user, 'Phone');

        app(ResetUserPassword::class)->reset($user, [
            'password' => 'An0ther!Passw0rd#2026',
            'password_confirmation' => 'An0ther!Passw0rd#2026',
        ]);

        $this->assertTrue(Token::query()->where('user_id', $user->id)->sole()->revoked);
    }
}
