<?php

namespace Tests\Feature\Modules\User;

use Artwork\Core\Mail\MailService;
use Artwork\Modules\ExternalUserManagement\Models\ExternalUserSource;
use Artwork\Modules\ExternalUserManagement\Service\OidcService;
use Artwork\Modules\Permission\Enums\PermissionEnum;
use Artwork\Modules\Role\Enums\RoleEnum;
use Artwork\Modules\Shift\Models\Shift;
use Artwork\Modules\Shift\Models\ShiftQualification;
use Artwork\Modules\Shift\Models\ShiftWorker;
use Artwork\Modules\User\Models\User;
use Artwork\Modules\User\Models\UserShiftCalendarAbo;
use Artwork\Modules\User\Models\UserWorkTime;
use Artwork\Modules\User\Services\UserDeactivationService;
use Artwork\Modules\User\Services\UserService;
use Artwork\Modules\WorkTime\Services\WorkTimeCalculationService;
use Carbon\Carbon;
use Illuminate\Notifications\Events\NotificationSending;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

/**
 * Accounts deaktivieren statt löschen: Login gesperrt, Daten und Zuordnungen bleiben, Reaktivierung möglich.
 */
final class UserDeactivationTest extends FeatureTestCase
{
    private function deactivated(array $attributes = []): User
    {
        return User::factory()->create(array_merge(['email' => 'paused@example.com'], $attributes))
            ->forceFill(['deactivated_at' => now()->subDay()]);
    }

    private function deactivatedAndSaved(array $attributes = []): User
    {
        $user = $this->deactivated($attributes);
        $user->save();

        return $user;
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->spy(MailService::class);
    }

    #[Test]
    public function hr_admin_can_deactivate_and_reactivate_an_account(): void
    {
        $actor = $this->actingAsUserWith(PermissionEnum::MA_MANAGER->value);
        $user = User::factory()->create();

        $this->post(route('user.deactivate', $user))->assertRedirect()->assertSessionHas('success');

        $user->refresh();
        $this->assertTrue($user->isDeactivated());
        $this->assertSame($actor->id, $user->deactivated_by);

        $this->post(route('user.reactivate', $user))->assertRedirect()->assertSessionHas('success');

        $user->refresh();
        $this->assertFalse($user->isDeactivated());
        $this->assertNull($user->deactivated_by);
    }

    #[Test]
    public function deactivation_ends_open_browser_sessions(): void
    {
        config(['session.driver' => 'database']);
        $user = User::factory()->create();
        DB::table('sessions')->insert([
            'id' => Str::random(40),
            'user_id' => $user->id,
            'payload' => '',
            'last_activity' => now()->timestamp,
        ]);

        app(UserDeactivationService::class)->deactivate($user, User::factory()->create());

        $this->assertDatabaseMissing('sessions', ['user_id' => $user->id]);
    }

    #[Test]
    public function deactivation_keeps_permissions_and_team_assignments(): void
    {
        $this->actingAsUserWith(PermissionEnum::MA_MANAGER->value);
        $user = $this->userWithPermissionsFor(PermissionEnum::PROJECT_VIEW->value);

        $this->post(route('user.deactivate', $user))->assertRedirect();

        $this->assertTrue($user->fresh()->hasPermissionTo(PermissionEnum::PROJECT_VIEW->value));
    }

    #[Test]
    public function users_without_hr_permission_cannot_deactivate(): void
    {
        $this->actingAs(User::factory()->create());
        $user = User::factory()->create();

        $this->post(route('user.deactivate', $user))->assertForbidden();
        $this->post(route('user.reactivate', $this->deactivatedAndSaved()))->assertForbidden();

        $this->assertFalse($user->fresh()->isDeactivated());
    }

    #[Test]
    public function external_worker_permission_alone_cannot_deactivate_accounts(): void
    {
        $this->actingAsUserWith(PermissionEnum::EXTERNAL_MANAGER->value);

        $this->post(route('user.deactivate', User::factory()->create()))->assertForbidden();
    }

    #[Test]
    public function only_admins_can_deactivate_admin_accounts(): void
    {
        $admin = $this->actingAsAdmin();
        $this->actingAsUserWith(PermissionEnum::MA_MANAGER->value);

        $this->post(route('user.deactivate', $admin))->assertForbidden();
        $this->assertFalse($admin->fresh()->isDeactivated());

        $this->actingAsAdmin();
        $this->post(route('user.deactivate', $admin))->assertRedirect();
        $this->assertTrue($admin->fresh()->isDeactivated());
    }

    #[Test]
    public function nobody_can_deactivate_their_own_account(): void
    {
        $admin = $this->actingAsAdmin();

        $this->post(route('user.deactivate', $admin))->assertRedirect()->assertSessionHas('error');

        $this->assertFalse($admin->fresh()->isDeactivated());
    }

    #[Test]
    public function deactivated_admins_do_not_count_as_remaining_admins(): void
    {
        $admin = $this->actingAsAdmin();
        $otherAdmin = $this->deactivatedAndSaved();
        $otherAdmin->assignRole(RoleEnum::ARTWORK_ADMIN->value);

        $this->assertTrue(app(UserService::class)->isLastAdmin($admin));
    }

    #[Test]
    public function deactivated_accounts_cannot_sign_in_with_their_password(): void
    {
        $this->deactivatedAndSaved();

        $this->post('/login', ['email' => 'paused@example.com', 'password' => 'password'])
            ->assertSessionHasErrors(['email' => __('flash-messages.user.error.account_deactivated')]);

        $this->assertGuest();
    }

    #[Test]
    public function a_wrong_password_does_not_reveal_the_deactivation(): void
    {
        $this->deactivatedAndSaved();

        $this->post('/login', ['email' => 'paused@example.com', 'password' => 'wrong-password'])
            ->assertSessionHasErrors('email')
            ->assertSessionDoesntHaveErrors(['email' => __('flash-messages.user.error.account_deactivated')]);
    }

    #[Test]
    public function reactivated_accounts_can_sign_in_again(): void
    {
        $user = $this->deactivatedAndSaved();
        $user->forceFill(['deactivated_at' => null])->save();

        $this->post('/login', ['email' => 'paused@example.com', 'password' => 'password'])->assertRedirect();

        $this->assertAuthenticatedAs($user);
    }

    #[Test]
    public function running_sessions_and_tokens_no_longer_resolve_the_account(): void
    {
        $user = $this->deactivatedAndSaved();

        // Session- und Passport-Guard lösen Benutzer über den Provider auf – deaktivierte gelten als unbekannt.
        $this->assertNull(Auth::guard('web')->getProvider()->retrieveById($user->id));
        $this->assertNull(Auth::createUserProvider('users')->retrieveByCredentials(['email' => $user->email]));

        $user->forceFill(['deactivated_at' => null])->save();
        $this->assertNotNull(Auth::guard('web')->getProvider()->retrieveById($user->id));
    }

    #[Test]
    public function deactivated_accounts_cannot_sign_in_via_sso(): void
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
                'identifier' => 'subject-123',
                'email' => 'oidc.user@example.com',
                'first_name' => 'Oidc',
                'last_name' => 'User',
                'groups' => [],
                'email_verified' => true,
                'meta_data' => ['sub' => 'subject-123'],
            ]);
        });

        $this->get(route('auth.oidc.callback', ['externalUserSource' => $source->id]))->assertRedirect();
        $user = User::query()->where('email', 'oidc.user@example.com')->sole();
        Auth::logout();
        $user->forceFill(['deactivated_at' => now()])->save();

        $this->get(route('auth.oidc.callback', ['externalUserSource' => $source->id]))
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors(['email' => __('flash-messages.user.error.account_deactivated')]);

        $this->assertGuest();
        $this->assertTrue($user->fresh()->isDeactivated());
    }

    #[Test]
    public function shift_calendar_feeds_of_deactivated_accounts_stop_delivering(): void
    {
        $user = User::factory()->create();
        $abo = new UserShiftCalendarAbo();
        $abo->forceFill([
            'user_id' => $user->id,
            'calendar_abo_id' => (string) Str::uuid(),
            'date_range' => false,
            'specific_crafts' => false,
            'enable_notification' => false,
        ])->save();

        $this->get(route('user-shift-calendar-abo.show', $abo->calendar_abo_id))->assertOk();

        $user->forceFill(['deactivated_at' => now()])->save();

        $this->get(route('user-shift-calendar-abo.show', $abo->calendar_abo_id))->assertNotFound();
    }

    #[Test]
    public function deactivated_accounts_receive_no_notifications(): void
    {
        $notification = new class extends Notification {
        };

        $this->assertFalse(
            Event::until(new NotificationSending($this->deactivatedAndSaved(), $notification, 'mail'))
        );
        $this->assertNull(
            Event::until(new NotificationSending(User::factory()->create(), $notification, 'mail'))
        );
    }

    #[Test]
    public function user_list_shows_deactivated_accounts_only_to_hr_admins(): void
    {
        $active = User::factory()->create();
        $deactivated = $this->deactivatedAndSaved();

        $this->actingAsUserWith(PermissionEnum::MA_MANAGER->value);
        $this->get(route('users'))->assertInertia(function (AssertableInertia $page) use ($active, $deactivated): void {
            $userIds = collect($page->toArray()['props']['users'])->pluck('id');
            $deactivatedIds = collect($page->toArray()['props']['deactivatedUsers'])->pluck('id');

            $this->assertTrue($userIds->contains($active->id));
            $this->assertFalse($userIds->contains($deactivated->id));
            $this->assertSame([$deactivated->id], $deactivatedIds->all());
        });

        $this->actingAs(User::factory()->create());
        $this->get(route('users'))->assertInertia(function (AssertableInertia $page) use ($deactivated): void {
            $this->assertSame([], $page->toArray()['props']['deactivatedUsers']);
            $this->assertFalse(collect($page->toArray()['props']['users'])->pluck('id')->contains($deactivated->id));
        });
    }

    #[Test]
    public function people_search_skips_deactivated_accounts(): void
    {
        $this->actingAs(User::factory()->create());
        $active = User::factory()->create(['first_name' => 'Zacharias']);
        $deactivated = $this->deactivatedAndSaved(['first_name' => 'Zacharias']);

        $ids = collect($this->getJson(route('users.search', ['query' => 'Zacharias']))->json())->pluck('id');

        $this->assertTrue($ids->contains($active->id));
        $this->assertFalse($ids->contains($deactivated->id));
        $this->assertFalse($deactivated->shouldBeSearchable());
    }

    #[Test]
    public function tooltip_marks_deactivated_accounts(): void
    {
        $this->actingAs(User::factory()->create());
        $deactivated = $this->deactivatedAndSaved();

        $this->getJson(route('user.tooltip.info', $deactivated))
            ->assertOk()
            ->assertJsonPath('deactivated_at', $deactivated->deactivated_at->toIso8601String());
        $this->getJson(route('user.tooltip.info', User::factory()->create()))
            ->assertOk()
            ->assertJsonPath('deactivated_at', null);
    }

    #[Test]
    public function inviting_a_deactivated_email_points_to_reactivation(): void
    {
        $this->actingAsUserWith(PermissionEnum::MA_MANAGER->value);
        $this->deactivatedAndSaved();

        $this->postJson(route('invitations.store'), ['user_emails' => ['paused@example.com']])
            ->assertJsonValidationErrors([
                'user_emails.0' => __('There is a deactivated account for this email address. Reactivate it in the user list.'),
            ]);
    }

    #[Test]
    public function no_target_hours_accrue_from_the_deactivation_day(): void
    {
        $user = User::factory()->create(['can_work_shifts' => true]);
        UserWorkTime::query()->insert([
            'user_id' => $user->id,
            'monday' => '08:00',
            'tuesday' => '08:00',
            'wednesday' => '08:00',
            'thursday' => '08:00',
            'friday' => '08:00',
            'valid_from' => '2026-01-01',
            'valid_until' => null,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $user->forceFill(['deactivated_at' => Carbon::parse('2026-06-10 15:30')])->save();
        $service = app(WorkTimeCalculationService::class);

        // Di 09.06. noch aktiv, Mi 10.06. (Deaktivierungstag) und danach kein Soll mehr
        $this->assertSame(480, $service->baseTargetMinutes($user, Carbon::parse('2026-06-09')));
        $this->assertSame(0, $service->baseTargetMinutes($user, Carbon::parse('2026-06-10')));
        $this->assertSame(0, $service->baseTargetMinutes($user, Carbon::parse('2026-06-11')));
    }

    #[Test]
    public function shift_plan_keeps_deactivated_people_only_with_shifts_in_the_period(): void
    {
        $withShift = $this->deactivatedAndSaved(['email' => 'with-shift@example.com', 'can_work_shifts' => true]);
        $withoutShift = $this->deactivatedAndSaved(['email' => 'without-shift@example.com', 'can_work_shifts' => true]);
        $active = User::factory()->create(['can_work_shifts' => true]);
        $shift = Shift::factory()->create(['start_date' => '2026-06-10', 'end_date' => '2026-06-10']);
        ShiftWorker::create([
            'shift_id' => $shift->id,
            'employable_type' => User::class,
            'employable_id' => $withShift->id,
            'shift_qualification_id' => ShiftQualification::factory()->create()->id,
        ]);

        $ids = User::query()
            ->activeOrWithShiftsBetween(Carbon::parse('2026-06-08'), Carbon::parse('2026-06-14'))
            ->pluck('id');

        $this->assertTrue($ids->contains($active->id));
        $this->assertTrue($ids->contains($withShift->id));
        $this->assertFalse($ids->contains($withoutShift->id));

        $idsOtherWeek = User::query()
            ->activeOrWithShiftsBetween(Carbon::parse('2026-07-06'), Carbon::parse('2026-07-12'))
            ->pluck('id');
        $this->assertFalse($idsOtherWeek->contains($withShift->id));
    }

    private function userWithPermissionsFor(string $permission): User
    {
        $user = User::factory()->create();
        \Spatie\Permission\Models\Permission::findOrCreate($permission, 'web');
        $user->givePermissionTo($permission);

        return $user;
    }
}
