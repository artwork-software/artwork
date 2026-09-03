<?php

namespace Tests\Feature\Authorization;

use Artwork\Core\Mail\MailService;
use Artwork\Modules\Checklist\Models\Checklist;
use Artwork\Modules\Freelancer\Models\Freelancer;
use Artwork\Modules\InternalIssue\Models\InternalIssue;
use Artwork\Modules\Invitation\Models\Invitation;
use Artwork\Modules\ModuleSettings\Http\Middleware\ModuleSettingsMiddleware;
use Artwork\Modules\Permission\Catalog\PermissionCatalog;
use Artwork\Modules\Permission\Enums\PermissionEnum;
use Artwork\Modules\Permission\Models\Permission;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\Role\Enums\RoleEnum;
use Artwork\Modules\User\Models\User;
use Artwork\Modules\User\Services\UserService;
use Inertia\Testing\AssertableInertia;
use PHPUnit\Framework\Attributes\Test;
use Tests\CreateAdminUser;
use Tests\Feature\FeatureTestCase;

/**
 * Release-Review 03.09.2026 (dev → staging): Härtungen vor dem Prod-Deploy.
 */
final class ReleaseHardeningTest extends FeatureTestCase
{
    use CreateAdminUser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->spy(MailService::class);
    }

    // ---------- Einladung: Admin-Rolle nur durch Admins ----------

    #[Test]
    public function hr_manager_can_invite_but_not_assign_the_admin_role(): void
    {
        $this->actingAsUserWith(PermissionEnum::MA_MANAGER->value);

        $this->postJson(route('invitations.store'), [
            'user_emails' => ['invitee@example.com'],
            'roles' => [RoleEnum::ARTWORK_ADMIN->value],
            'permissions' => [],
        ])->assertUnprocessable()->assertJsonValidationErrors(['roles.0']);
        $this->assertDatabaseCount('invitations', 0);

        $this->postJson(route('invitations.store'), [
            'user_emails' => ['invitee@example.com'],
            'roles' => [],
            'permissions' => [],
        ])->assertRedirect(route('users'));
        $this->assertDatabaseCount('invitations', 1);
    }

    #[Test]
    public function admin_can_still_invite_with_the_admin_role(): void
    {
        $this->actingAs($this->adminUser());

        $this->postJson(route('invitations.store'), [
            'user_emails' => ['invitee@example.com'],
            'roles' => [RoleEnum::ARTWORK_ADMIN->value],
            'permissions' => [],
        ])->assertRedirect(route('users'));

        $this->assertSame([RoleEnum::ARTWORK_ADMIN->value], Invitation::query()->firstOrFail()->roles);
    }

    #[Test]
    public function users_index_offers_roles_only_to_admins(): void
    {
        $this->actingAsUserWith(PermissionEnum::MA_MANAGER->value);
        $this->get(route('users'))->assertInertia(
            fn (AssertableInertia $page) => $page->component('Users/Index')->where('roles', [])
        );

        $this->actingAs($this->adminUser());
        $this->get(route('users'))->assertInertia(
            fn (AssertableInertia $page) => $page->component('Users/Index')
                ->where('roles.0.name', RoleEnum::ARTWORK_ADMIN->value)
        );
    }

    // ---------- Einladung annehmen mit entfallenem Recht ----------

    #[Test]
    public function accepting_an_invitation_with_an_unknown_permission_does_not_crash(): void
    {
        Permission::findOrCreate(PermissionEnum::PROJECT_VIEW->value, 'web');

        $user = app(UserService::class)->create(
            User::factory()->raw(),
            ['no such role'],
            ['can use checklists', PermissionEnum::PROJECT_VIEW->value],
            []
        );

        $this->assertSame([PermissionEnum::PROJECT_VIEW->value], $user->permissions->pluck('name')->all());
        $this->assertSame([], $user->roles->pluck('name')->all());
    }

    #[Test]
    public function catalog_cleanup_migration_rewrites_open_invitations_and_maps_external_conditions(): void
    {
        $old = Permission::findOrCreate('can edit external users conditions', 'web');
        Permission::findOrCreate('can use checklists', 'web');
        Permission::findOrCreate(PermissionEnum::PROJECT_VIEW->value, 'web');
        $holder = User::factory()->create();
        $holder->givePermissionTo($old);
        $invitation = Invitation::factory()->create([
            'permissions' => ['can use checklists', 'can edit external users conditions', PermissionEnum::PROJECT_VIEW->value],
        ]);

        (require base_path('database/migrations/2026_09_02_140000_permission_catalog_cleanup.php'))->up();

        $this->assertSame(
            [PermissionEnum::PROJECT_VIEW->value, PermissionEnum::EXTERNAL_MANAGER->value],
            $invitation->fresh()->permissions
        );
        $this->assertTrue($holder->fresh()->hasPermissionTo(PermissionEnum::EXTERNAL_MANAGER->value));
        $this->assertFalse($holder->fresh()->hasPermissionTo(PermissionEnum::MA_MANAGER->value));
        $this->assertDatabaseMissing('permissions', ['name' => 'can edit external users conditions']);
        $this->assertDatabaseMissing('permissions', ['name' => 'can use checklists']);
    }

    // ---------- Eigenes Recht "Externe verwalten" ----------

    #[Test]
    public function hr_administration_implies_external_worker_administration(): void
    {
        $expanded = app(PermissionCatalog::class)->expandWithImplied([PermissionEnum::MA_MANAGER->value]);

        $this->assertContains(PermissionEnum::EXTERNAL_MANAGER->value, $expanded);
        $this->assertNotContains(
            PermissionEnum::MA_MANAGER->value,
            app(PermissionCatalog::class)->expandWithImplied([PermissionEnum::EXTERNAL_MANAGER->value])
        );
    }

    #[Test]
    public function external_manager_maintains_freelancers_but_not_in_house_staff(): void
    {
        $freelancer = Freelancer::factory()->create();
        $colleague = User::factory()->create();

        $this->actingAs(User::factory()->create());
        $this->patch(route('freelancer.update.terms', $freelancer), ['salary_per_hour' => 50])->assertForbidden();
        $this->get(route('freelancer.show', $freelancer))->assertForbidden();

        $manager = $this->actingAsUserWith(PermissionEnum::EXTERNAL_MANAGER->value);
        $this->patch(route('freelancer.update.terms', $freelancer), ['salary_per_hour' => 50])->assertSuccessful();
        $this->assertSame(50, (int) $freelancer->fresh()->salary_per_hour);
        $this->get(route('freelancer.show', $freelancer))->assertSuccessful();

        $this->assertFalse($manager->can('create', Invitation::class));
        $this->assertFalse($manager->can('updateTerms', $colleague));
    }

    // ---------- Materialausgaben lesen ----------

    #[Test]
    public function project_reader_can_view_but_not_manage_material_issues_of_the_project(): void
    {
        $project = Project::factory()->create();
        $user = User::factory()->create();
        $project->users()->attach($user->id, ['can_write' => false]);
        $own = InternalIssue::factory()->create(['project_id' => $project->id]);
        $foreign = InternalIssue::factory()->create();

        $this->assertTrue($user->can('view', $own));
        $this->assertFalse($user->can('update', $own));
        $this->assertFalse($user->can('view', $foreign));
    }

    // ---------- Checklisten folgen dem Projektrecht ----------

    #[Test]
    public function global_project_writer_and_project_creator_can_edit_project_checklists(): void
    {
        $project = Project::factory()->create();
        $checklist = Checklist::factory()->create([
            'project_id' => $project->id,
            'user_id' => User::factory()->create()->id,
        ]);

        $writer = $this->actingAsUserWith(PermissionEnum::WRITE_PROJECTS->value);
        $this->assertTrue($writer->can('view', $checklist));
        $this->assertTrue($writer->can('update', $checklist));
        $this->assertTrue($writer->can('delete', $checklist));

        $creator = User::factory()->create();
        $project->update(['user_id' => $creator->id]);
        $this->assertTrue($creator->can('update', $checklist->fresh()));

        $this->assertFalse(User::factory()->create()->can('update', $checklist->fresh()));
    }

    // ---------- Modul-Schalter: Querschnitts-Endpunkte bleiben erreichbar ----------

    #[Test]
    public function module_middleware_keeps_cross_module_endpoints_reachable(): void
    {
        // Projekt-Endpunkte nutzen Kalender, Dienstplan, Budget: nur die Einstiegsseite hängt am Schalter
        $this->assertSame('projects', ModuleSettingsMiddleware::resolveSetting('/projects'));
        $this->assertNull(ModuleSettingsMiddleware::resolveSetting('/projects/5/tabs/3'));

        $this->assertNull(ModuleSettingsMiddleware::resolveSetting('/money_sources/search/money_source'));
        $this->assertSame('sources_of_funding', ModuleSettingsMiddleware::resolveSetting('/money_sources/5'));

        $this->assertNull(ModuleSettingsMiddleware::resolveSetting('/contracts/12'));
        $this->assertNull(ModuleSettingsMiddleware::resolveSetting('/contracts/12/download'));
        $this->assertSame('contracts', ModuleSettingsMiddleware::resolveSetting('/contracts/view'));
        $this->assertSame('contracts', ModuleSettingsMiddleware::resolveSetting('/contracts/export/excel'));

        $this->assertNull(ModuleSettingsMiddleware::resolveSetting('/crm/contacts-search'));
        $this->assertNull(ModuleSettingsMiddleware::resolveSetting('/crm/contacts/3/tooltip'));
        $this->assertNull(ModuleSettingsMiddleware::resolveSetting('/crm/contacts/3/data'));
        $this->assertSame('crm', ModuleSettingsMiddleware::resolveSetting('/crm/contacts/3'));
        $this->assertSame('crm', ModuleSettingsMiddleware::resolveSetting('/crm'));
    }
}
