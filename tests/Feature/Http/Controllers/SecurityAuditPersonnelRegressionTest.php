<?php

namespace Tests\Feature\Http\Controllers;

use Artwork\Core\Mail\MailService;
use Artwork\Modules\Craft\Models\Craft;
use Artwork\Modules\Department\Models\Department;
use Artwork\Modules\IndividualTimes\Models\IndividualTime;
use Artwork\Modules\IndividualTimes\Models\IndividualTimeSeries;
use Artwork\Modules\Invitation\Models\Invitation;
use Artwork\Modules\Permission\Enums\PermissionEnum;
use Artwork\Modules\Project\Models\ProjectRole;
use Artwork\Modules\ServiceProvider\Models\ServiceProvider;
use Artwork\Modules\Shift\Models\ShiftFilter;
use Artwork\Modules\Shift\Models\ShiftPlanRequest;
use Artwork\Modules\User\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

/**
 * Regressionstests zum Sicherheits-Audit vom 21.09.2026, Abschnitt B (Personal/Schicht/User/Einladungen)
 * sowie die Privilege-Escalation-Befunde aus Abschnitt D (Einladungen).
 */
final class SecurityAuditPersonnelRegressionTest extends FeatureTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Mail::fake() (FeatureTestCase) ersetzt den MailManager durch ein MailFake, an dem der hart
        // typisierte MailService-Konstruktor scheitert (InvitationController/-Service) — daher spyen.
        $this->spy(MailService::class);
    }

    // ------------------------------------------------------------------
    // Sofortmaßnahme 4a: updateUserDetails (Admin-Übernahme via E-Mail)
    // ------------------------------------------------------------------

    #[Test]
    public function personnel_manager_cannot_change_the_email_of_another_user(): void
    {
        $this->actingAsUserWith([PermissionEnum::MA_MANAGER]);
        $target = User::factory()->create(['email' => 'target@example.test']);

        $this->patch(
            route('user.update', $target),
            $this->userDetailsPayload($target, ['email' => 'hijack@example.test'])
        )->assertForbidden();

        $this->assertSame('target@example.test', $target->fresh()->email);
    }

    #[Test]
    public function personnel_manager_can_still_edit_other_users_without_changing_the_email(): void
    {
        $this->actingAsUserWith([PermissionEnum::MA_MANAGER]);
        $target = User::factory()->create(['email' => 'target@example.test']);

        $this->patch(
            route('user.update', $target),
            $this->userDetailsPayload($target, ['first_name' => 'Renamed'])
        )->assertRedirect();

        $this->assertSame('Renamed', $target->fresh()->first_name);
        $this->assertSame('target@example.test', $target->fresh()->email);
    }

    #[Test]
    public function personnel_manager_cannot_edit_an_admin_account(): void
    {
        $this->actingAsUserWith([PermissionEnum::MA_MANAGER]);
        $admin = $this->adminUser();

        $this->patch(
            route('user.update', $admin),
            $this->userDetailsPayload($admin, ['first_name' => 'Owned'])
        )->assertForbidden();

        $this->assertNotSame('Owned', $admin->fresh()->first_name);
    }

    #[Test]
    public function users_can_change_their_own_email_and_admins_can_change_any(): void
    {
        $self = $this->actingAsUserWith([]);
        $this->patch(
            route('user.update', $self),
            $this->userDetailsPayload($self, ['email' => 'me-new@example.test'])
        )->assertRedirect();
        $this->assertSame('me-new@example.test', $self->fresh()->email);

        $this->actingAsAdmin();
        $target = User::factory()->create();
        $this->patch(
            route('user.update', $target),
            $this->userDetailsPayload($target, ['email' => 'by-admin@example.test'])
        )->assertRedirect();
        $this->assertSame('by-admin@example.test', $target->fresh()->email);
    }

    // ------------------------------------------------------------------
    // Sofortmaßnahme 4b: Einladungs-Rechte auf eigene Rechte begrenzt
    // ------------------------------------------------------------------

    #[Test]
    public function invitation_permissions_are_filtered_to_the_inviters_own_permissions(): void
    {
        $this->actingAsUserWith([PermissionEnum::MA_MANAGER, PermissionEnum::PROJECT_VIEW]);

        $this->postJson(route('invitations.store'), [
            'user_emails' => ['invitee@example.test'],
            'roles' => [],
            'permissions' => [
                PermissionEnum::PROJECT_VIEW->value,
                PermissionEnum::PROJECT_MANAGEMENT->value,
                PermissionEnum::SHIFT_PLANNER->value,
            ],
        ])->assertRedirect(route('users'));

        $invitation = Invitation::query()->where('email', 'invitee@example.test')->firstOrFail();
        $this->assertSame([PermissionEnum::PROJECT_VIEW->value], $invitation->permissions);
        $this->assertNotNull($invitation->expires_at);
        $this->assertTrue($invitation->expires_at->between(now()->addDays(6), now()->addDays(8)));
    }

    #[Test]
    public function admins_can_invite_with_any_permission(): void
    {
        $this->actingAsAdmin();

        $this->postJson(route('invitations.store'), [
            'user_emails' => ['invitee@example.test'],
            'roles' => [],
            'permissions' => [PermissionEnum::PROJECT_MANAGEMENT->value, PermissionEnum::SHIFT_PLANNER->value],
        ])->assertRedirect(route('users'));

        $invitation = Invitation::query()->where('email', 'invitee@example.test')->firstOrFail();
        $this->assertEqualsCanonicalizing(
            [PermissionEnum::PROJECT_MANAGEMENT->value, PermissionEnum::SHIFT_PLANNER->value],
            $invitation->permissions
        );
    }

    #[Test]
    public function invite_page_requires_the_invitation_create_permission(): void
    {
        $this->actingAsUserWith([]);
        $this->get(route('user.invite'))->assertForbidden();

        $this->actingAsUserWith([PermissionEnum::MA_MANAGER]);
        $this->get(route('user.invite'))->assertOk();
    }

    // ------------------------------------------------------------------
    // Sofortmaßnahme 4c: Einladungs-Ablauf + Throttle
    // ------------------------------------------------------------------

    #[Test]
    public function expired_invitations_cannot_be_opened_or_accepted(): void
    {
        $token = Str::random(20);
        $invitation = Invitation::factory()->expired()->create([
            'email' => 'late@example.test',
            'token' => Hash::make($token),
            'permissions' => [],
            'roles' => [],
        ]);

        // Abgelaufen, aber Token gültig: Hinweisseite statt 401 (Sicherheits-Audit 21.09.2026, Restpunkt 6)
        $this->get($this->acceptUrl('late@example.test', $token))
            ->assertOk()
            ->assertInertia(static fn ($page) => $page->component('Users/InvitationExpired'));

        $this->post(route('invitation.accept'), $this->acceptPayload('late@example.test', $token))
            ->assertForbidden();

        $this->assertDatabaseMissing('users', ['email' => 'late@example.test']);
        $this->assertDatabaseHas('invitations', ['id' => $invitation->id]);
    }

    #[Test]
    public function valid_invitations_can_still_be_accepted(): void
    {
        $token = Str::random(20);
        Invitation::factory()->create([
            'email' => 'fresh@example.test',
            'token' => Hash::make($token),
            'permissions' => [],
            'roles' => [],
            'expires_at' => Carbon::now()->addDays(3),
        ]);

        $this->get($this->acceptUrl('fresh@example.test', $token))->assertOk();

        $this->post(route('invitation.accept'), $this->acceptPayload('fresh@example.test', $token))
            ->assertRedirect(route('dashboard'));

        $this->assertDatabaseHas('users', ['email' => 'fresh@example.test']);
        $this->assertDatabaseMissing('invitations', ['email' => 'fresh@example.test']);
    }

    #[Test]
    public function invitation_accept_routes_are_throttled(): void
    {
        $post = Route::getRoutes()->getByName('invitation.accept');
        $this->assertContains('throttle:10,1', $post->gatherMiddleware());

        $get = collect(Route::getRoutes()->getRoutes())
            ->first(
                fn ($route) => $route->uri() === 'users/invitations/accept' && in_array('GET', $route->methods(), true)
            );
        $this->assertNotNull($get);
        $this->assertContains('throttle:10,1', $get->gatherMiddleware());
    }

    // ------------------------------------------------------------------
    // HOCH: Individualzeit-Serien
    // ------------------------------------------------------------------

    #[Test]
    public function individual_time_series_cannot_be_created_for_other_users_without_permission(): void
    {
        $this->actingAsUserWith([]);
        $victim = User::factory()->create();

        $this->post(
            route('individual-time-series.store'),
            $this->seriesPayload([['type' => 'user', 'id' => $victim->id]])
        )->assertForbidden();

        $this->assertDatabaseCount('individual_time_series', 0);
        $this->assertSame(0, IndividualTime::query()->where('timeable_id', $victim->id)->count());
    }

    #[Test]
    public function individual_time_series_can_be_created_for_self_or_with_availability_management(): void
    {
        $self = $this->actingAsUserWith([]);
        $this->post(
            route('individual-time-series.store'),
            $this->seriesPayload([['type' => 'user', 'id' => $self->id]])
        )->assertRedirect();
        $this->assertSame(1, IndividualTimeSeries::query()->count());

        $this->actingAsUserWith([PermissionEnum::AVAILABILITY_MANAGEMENT]);
        $other = User::factory()->create();
        $this->post(
            route('individual-time-series.store'),
            $this->seriesPayload([['type' => 'user', 'id' => $other->id]])
        )->assertRedirect();
        $this->assertSame(2, IndividualTimeSeries::query()->count());
    }

    #[Test]
    public function mixed_subjects_require_permission_for_every_subject(): void
    {
        $self = $this->actingAsUserWith([]);
        $other = User::factory()->create();

        $this->post(route('individual-time-series.store'), $this->seriesPayload([
            ['type' => 'user', 'id' => $self->id],
            ['type' => 'user', 'id' => $other->id],
        ]))->assertForbidden();

        $this->assertDatabaseCount('individual_time_series', 0);
    }

    #[Test]
    public function foreign_individual_time_series_cannot_be_updated_or_deleted_without_permission(): void
    {
        $owner = User::factory()->create();
        $series = $this->createSeriesFor($owner);

        $this->actingAsUserWith([]);
        $this->put(
            route('individual-time-series.update', $series),
            $this->seriesPayload([['type' => 'user', 'id' => $owner->id]])
        )->assertForbidden();
        $this->delete(route('individual-time-series.destroy', $series))->assertForbidden();

        $this->assertDatabaseHas('individual_time_series', ['uuid' => $series->uuid]);
        $this->assertSame(1, $series->individualTimes()->count());

        $this->actingAsUserWith([PermissionEnum::AVAILABILITY_MANAGEMENT]);
        $this->delete(route('individual-time-series.destroy', $series))->assertOk();
        $this->assertDatabaseMissing('individual_time_series', ['uuid' => $series->uuid]);
    }

    // ------------------------------------------------------------------
    // HOCH: API-Duplikate der Regelverstoß-Endpunkte (ungenutzt → entfernt)
    // ------------------------------------------------------------------

    #[Test]
    public function unguarded_api_duplicates_of_shift_rule_endpoints_are_gone(): void
    {
        $this->assertFalse(Route::has('api.shift-rules.validate'));
        $this->assertFalse(Route::has('api.shift-rules.pending'));
        $this->assertFalse(Route::has('api.shift-rules.update-status'));
        $this->assertTrue(Route::has('shift-rules.pending'));
    }

    // ------------------------------------------------------------------
    // HOCH: Dienstleister-Kontaktpersonen
    // ------------------------------------------------------------------

    /**
     * update/destroy samt Legacy-Model ServiceProviderContacts (Tabelle existiert nicht mehr) wurden
     * entfernt — store legt über HasContacts einen Kontakt des Contacts-Moduls an.
     */
    #[Test]
    public function service_provider_contacts_require_external_management_rights(): void
    {
        $provider = ServiceProvider::factory()->create();

        $this->actingAsUserWith([]);
        $this->post(route('service-provider.contact.store', $provider))->assertForbidden();
        $this->assertSame(0, $provider->contacts()->count());

        $this->actingAsUserWith([PermissionEnum::EXTERNAL_MANAGER]);
        $this->post(route('service-provider.contact.store', $provider))->assertOk();
        $this->assertSame(1, $provider->contacts()->count());
    }

    // ------------------------------------------------------------------
    // MITTEL: Dienstplan-JSON-APIs, Projektrollen, Abteilung leeren
    // ------------------------------------------------------------------

    #[Test]
    #[DataProvider('shiftPlanJsonRoutes')]
    public function shift_plan_json_endpoints_require_view_shift_plan(string $routeName): void
    {
        $this->actingAsUserWith([]);
        $this->getJson(route($routeName))->assertForbidden();

        $this->actingAsUserWith([PermissionEnum::VIEW_SHIFT_PLAN]);
        $this->assertNotSame(403, $this->getJson(route($routeName))->status());
    }

    public static function shiftPlanJsonRoutes(): iterable
    {
        yield 'shift.plan.all' => ['shift.plan.all'];
        yield 'shift.plan.meta' => ['shift.plan.meta'];
        yield 'shift.plan.room' => ['shift.plan.room'];
        yield 'shift.plan.rooms.batch' => ['shift.plan.rooms.batch'];
        yield 'events-and-workers' => ['shifts.events.for-rooms-by-days-and-project'];
        yield 'events-and-no-workers' => ['shifts.events.for-rooms-by-days-and-project-no-workers'];
    }

    #[Test]
    public function project_roles_require_project_settings_permission(): void
    {
        $this->actingAsUserWith([]);
        $this->get(route('project-roles.index'))->assertForbidden();
        $this->post(route('project-roles.store'), ['name' => 'Sneaky'])->assertForbidden();
        $this->assertDatabaseMissing('project_roles', ['name' => 'Sneaky']);

        $this->actingAsUserWith([PermissionEnum::PROJECT_SETTINGS_UPDATE]);
        $this->post(route('project-roles.store'), ['name' => 'Legit'])->assertOk();
        $this->assertDatabaseHas('project_roles', ['name' => 'Legit']);

        $role = ProjectRole::query()->where('name', 'Legit')->firstOrFail();
        $this->actingAsUserWith([]);
        $this->delete(route('project-roles.destroy', $role))->assertForbidden();
        $this->assertDatabaseHas('project_roles', ['id' => $role->id]);
    }

    #[Test]
    public function removing_all_department_members_requires_team_management(): void
    {
        $department = Department::factory()->create();
        $member = User::factory()->create();
        $department->users()->attach($member);

        $this->actingAsUserWith([]);
        $this->patch(route('departments.remove.members', $department))->assertForbidden();
        $this->assertSame(1, $department->users()->count());

        $this->actingAsUserWith([PermissionEnum::TEAM_UPDATE]);
        $this->patch(route('departments.remove.members', $department))->assertRedirect();
        $this->assertSame(0, $department->users()->count());
    }

    // ------------------------------------------------------------------
    // NIEDRIG: Owner-Checks, eigene Einstellungen, user-scoped find
    // ------------------------------------------------------------------

    #[Test]
    public function foreign_shift_filters_cannot_be_deleted(): void
    {
        $owner = User::factory()->create();
        $filter = ShiftFilter::query()->create(['name' => 'Mine', 'user_id' => $owner->id]);

        $this->actingAsUserWith([]);
        $this->delete('/shifts/filters/' . $filter->id)->assertForbidden();
        $this->assertDatabaseHas('shift_filters', ['id' => $filter->id]);

        $this->actingAs($owner);
        $this->delete('/shifts/filters/' . $filter->id)->assertOk();
        $this->assertDatabaseMissing('shift_filters', ['id' => $filter->id]);
    }

    #[Test]
    public function ui_preferences_and_calendar_filters_of_other_users_cannot_be_written(): void
    {
        $this->actingAsUserWith([]);
        $victim = User::factory()->create(['is_sidebar_opened' => true]);

        $this->patch(route('user.sidebar.update', $victim), ['is_sidebar_opened' => false])->assertForbidden();
        $this->assertTrue((bool) $victim->fresh()->is_sidebar_opened);

        $this->patch(route('update.user.calendar.filter', $victim), ['filter_type' => 'calendar_filter'])
            ->assertForbidden();
        $this->assertSame(0, $victim->userFilters()->count());

        $this->post(route('user.commentedBudgetItemsSettings.store', $victim), ['exclude' => true])->assertForbidden();
        $this->assertDatabaseMissing('user_commented_budget_items_settings', ['user_id' => $victim->id]);
    }

    #[Test]
    public function password_reset_mails_are_limited_to_own_address_or_personnel_management(): void
    {
        User::factory()->create(['email' => 'victim@example.test']);

        $this->actingAsUserWith([]);
        $this->post(route('user.reset.password'), ['email' => 'victim@example.test'])->assertForbidden();

        $this->actingAsUserWith([PermissionEnum::MA_MANAGER]);
        $this->assertNotSame(
            403,
            $this->post(route('user.reset.password'), ['email' => 'victim@example.test'])->status()
        );

        $this->assertContains(
            'throttle:10,1',
            Route::getRoutes()->getByName('user.reset.password')->gatherMiddleware()
        );
    }

    #[Test]
    public function past_shift_plan_requests_are_scoped_like_the_requests_page(): void
    {
        $craft = Craft::factory()->create(['assignable_by_all' => false]);
        $requester = User::factory()->create();
        $stranger = User::factory()->create();
        $old = Carbon::now()->subWeeks(8);
        foreach ([$requester, $stranger] as $by) {
            ShiftPlanRequest::query()->create([
                'craft_id' => $craft->id,
                'week_number' => (int) $old->format('W'),
                'year' => (int) $old->format('o'),
                'status' => 'approved',
                'requested_by_user_id' => $by->id,
                'requested_at' => $old,
            ]);
        }

        $this->actingAs($requester);
        $response = $this->getJson(route('shifts.approvals.past-requests', ['craft' => $craft, 'status' => 'approved']))
            ->assertOk();
        $this->assertCount(1, $response->json('requests'));
        $this->assertSame($requester->id, $response->json('requests.0.requested_by_user_id'));

        $craft->craftShiftPlaner()->attach($requester);
        $this->assertCount(
            2,
            $this->getJson(route('shifts.approvals.past-requests', ['craft' => $craft, 'status' => 'approved']))
                ->json('requests')
        );
    }

    // ------------------------------------------------------------------
    // Helpers
    // ------------------------------------------------------------------

    /** @return array<string, mixed> */
    private function userDetailsPayload(User $user, array $overrides = []): array
    {
        return array_merge([
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
            'email' => $user->email,
            'phone_number' => $user->phone_number,
            'position' => $user->position,
            'business' => $user->business,
            'description' => $user->description,
            'language' => $user->language ?? 'en',
            'high_contrast' => false,
            'departments' => [],
        ], $overrides);
    }

    private function acceptUrl(string $email, string $token): string
    {
        return '/users/invitations/accept?' . http_build_query(['email' => $email, 'token' => $token]);
    }

    /** @return array<string, mixed> */
    private function acceptPayload(string $email, string $token): array
    {
        return [
            'first_name' => 'New',
            'last_name' => 'User',
            'email' => $email,
            'password' => 'Str0ng!Passw0rd#2026',
            'password_confirmation' => 'Str0ng!Passw0rd#2026',
            'token' => $token,
        ];
    }

    /**
     * @param array<int, array{type: string, id: int}> $subjects
     * @return array<string, mixed>
     */
    private function seriesPayload(array $subjects): array
    {
        return [
            'title' => 'Serie',
            'start_date' => '2026-10-05',
            'end_date' => '2026-10-11',
            'full_day' => false,
            'start_time' => '09:00',
            'end_time' => '12:00',
            'break_minutes' => 0,
            'frequency' => 'weekly',
            'interval' => 1,
            'weekdays' => [1, 3],
            'subjects' => $subjects,
        ];
    }

    private function createSeriesFor(User $owner): IndividualTimeSeries
    {
        $series = IndividualTimeSeries::query()->create([
            'uuid' => (string) Str::uuid(),
            'title' => 'Bestand',
            'start_date' => '2026-10-05',
            'end_date' => '2026-10-05',
            'frequency' => 'weekly',
            'interval' => 1,
            'weekdays' => [1],
            'created_by' => $owner->id,
        ]);
        IndividualTime::query()->create([
            'series_uuid' => $series->uuid,
            'timeable_type' => User::class,
            'timeable_id' => $owner->id,
            'title' => 'Bestand',
            'start_date' => '2026-10-05',
            'end_date' => '2026-10-05',
            'start_time' => '09:00',
            'end_time' => '12:00',
            'full_day' => false,
            'working_time_minutes' => 180,
            'break_minutes' => 0,
        ]);

        return $series;
    }
}
