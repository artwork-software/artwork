<?php

namespace Tests\Feature\Authorization;

use Artwork\Modules\Checklist\Models\Checklist;
use Artwork\Modules\Contract\Models\ContractModule;
use Artwork\Modules\ExternalIssue\Models\ExternalIssue;
use Artwork\Modules\InternalIssue\Models\InternalIssue;
use Artwork\Modules\ModuleSettings\Http\Middleware\ModuleSettingsMiddleware;
use Artwork\Modules\MoneySource\Models\MoneySource;
use Artwork\Modules\Permission\Enums\PermissionEnum;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\Room\Models\Room;
use Artwork\Modules\User\Models\User;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

/**
 * Backend-Lücken aus KONZEPT_Nutzerrechte_Kommunikation.md, Abschnitt 7:
 * Rechte, die bisher nur den Menüpunkt versteckten, werden jetzt serverseitig durchgesetzt.
 */
final class PermissionGapClosureTest extends FeatureTestCase
{
    // ---------- Finanzierungsquellen ----------

    #[Test]
    public function money_source_update_requires_permission_or_membership(): void
    {
        $source = MoneySource::factory()->create();

        $this->actingAs(User::factory()->create());
        $this->patch(route('money_sources.update', $source), $this->moneySourcePayload($source))->assertForbidden();

        $this->actingAsUserWith(PermissionEnum::MONEY_SOURCE_EDIT_VIEW_ADD->value);
        $this->patch(route('money_sources.update', $source), $this->moneySourcePayload($source))->assertSuccessful();
    }

    #[Test]
    public function money_source_member_with_write_access_can_update(): void
    {
        $source = MoneySource::factory()->create();
        $user = User::factory()->create();
        $source->users()->attach($user->id, ['write_access' => true, 'competent' => false]);

        $this->actingAs($user);
        $this->patch(route('money_sources.update', $source), $this->moneySourcePayload($source))->assertSuccessful();
    }

    #[Test]
    public function money_source_delete_requires_delete_permission(): void
    {
        $source = MoneySource::factory()->create();

        $this->actingAsUserWith(PermissionEnum::MONEY_SOURCE_EDIT_VIEW_ADD->value);
        $this->delete(route('money_sources.destroy', $source))->assertForbidden();

        $this->actingAsUserWith(PermissionEnum::MONEY_SOURCE_EDIT_DELETE->value);
        $this->delete(route('money_sources.destroy', $source))->assertRedirect();
        $this->assertDatabaseMissing('money_sources', ['id' => $source->id]);
    }

    #[Test]
    public function money_source_store_requires_permission(): void
    {
        $this->actingAs(User::factory()->create());
        $this->post(route('money_sources.store'), ['name' => 'x', 'users' => []])->assertForbidden();
    }

    /** Update schreibt alle Felder ungeprüft aus dem Request — daher vollständiges Payload. */
    private function moneySourcePayload(MoneySource $source): array
    {
        return [
            'name' => 'Geändert',
            'amount' => (string) $source->amount,
            'start_date' => $source->start_date,
            'end_date' => $source->end_date,
            'source_name' => $source->source_name,
            'description' => $source->description,
            'is_group' => false,
            'users' => [],
        ];
    }

    // ---------- Räume ----------

    #[Test]
    public function room_store_and_delete_require_room_permission(): void
    {
        $room = Room::factory()->create();

        $this->actingAs(User::factory()->create());
        $this->post(route('rooms.store'), ['name' => 'Neu'])->assertForbidden();
        $this->delete('/rooms/' . $room->id)->assertForbidden();
        $this->patch(route('rooms.update', $room), ['name' => 'x'])->assertForbidden();

        $this->actingAsUserWith(PermissionEnum::ROOM_UPDATE->value);
        $this->delete('/rooms/' . $room->id)->assertRedirect();
        $this->assertSoftDeleted('rooms', ['id' => $room->id]);
    }

    #[Test]
    public function room_admin_can_update_own_room_but_not_others(): void
    {
        $own = Room::factory()->create();
        $other = Room::factory()->create();
        $user = User::factory()->create();
        $own->users()->attach($user->id, ['is_admin' => true, 'can_request' => false]);

        $this->actingAs($user);
        $this->patch(route('rooms.update', $own), ['name' => 'Eigener Raum'])->assertRedirect();
        $this->patch(route('rooms.update', $other), ['name' => 'Fremd'])->assertForbidden();
    }

    // ---------- Termineinstellungen ----------

    #[Test]
    public function event_type_and_status_mutations_require_event_settings_permission(): void
    {
        $this->actingAs(User::factory()->create());
        $this->post(route('event_types.store'), ['name' => 'x'])->assertForbidden();
        $this->post(route('event_status.store'), ['name' => 'x'])->assertForbidden();
        $this->post(route('event_settings.event_properties.store'), ['name' => 'x'])->assertForbidden();
    }

    // ---------- Materialausgaben ----------

    #[Test]
    public function material_issue_store_requires_disposition_or_project_write(): void
    {
        $this->actingAs(User::factory()->create());
        $this->post(route('issue-of-material.store'), [])->assertForbidden();
        $this->post(route('extern-issue-of-material.store'), [])->assertForbidden();
        $this->get(route('issue-of-material.index'))->assertForbidden();
    }

    #[Test]
    public function project_write_member_can_manage_material_issues_of_own_project(): void
    {
        $project = Project::factory()->create();
        $otherProject = Project::factory()->create();
        $user = User::factory()->create();
        $project->users()->attach($user->id, ['can_write' => true]);

        $own = InternalIssue::factory()->create(['project_id' => $project->id]);
        $foreign = InternalIssue::factory()->create(['project_id' => $otherProject->id]);

        $this->actingAs($user);
        $this->delete(route('issue-of-material.destroy', $own))->assertRedirect();
        $this->delete(route('issue-of-material.destroy', $foreign))->assertForbidden();
    }

    #[Test]
    public function external_issue_issuer_can_update_own_issue(): void
    {
        $user = User::factory()->create();
        $issue = ExternalIssue::factory()->create(['issued_by_id' => $user->id]);
        $foreign = ExternalIssue::factory()->create();

        $this->actingAs($user);
        $this->delete(route('extern-issue-of-material.destroy', $issue))->assertRedirect();
        $this->delete(route('extern-issue-of-material.destroy', $foreign))->assertForbidden();
    }

    // ---------- CRM ----------

    #[Test]
    public function crm_index_requires_view_permission(): void
    {
        $this->actingAs(User::factory()->create());
        $this->get(route('crm.index'))->assertForbidden();

        $this->actingAsUserWith(PermissionEnum::CRM_VIEW->value);
        $this->get(route('crm.index'))->assertSuccessful();
    }

    // ---------- Vertragsbausteine / Dokumentenanfragen ----------

    #[Test]
    public function contract_module_download_requires_read_permission_and_delete_requires_manage(): void
    {
        $module = ContractModule::create(['name' => 'Test', 'basename' => 'test']);

        $this->actingAs(User::factory()->create());
        $this->get(route('contracts.module.download', $module))->assertForbidden();
        $this->delete('/contract_modules/' . $module->id)->assertForbidden();

        $this->actingAsUserWith(PermissionEnum::CONTRACT_SEE_DOWNLOAD->value);
        $this->delete('/contract_modules/' . $module->id)->assertForbidden();

        $this->actingAsUserWith(PermissionEnum::CONTRACT_EDIT_UPLOAD->value);
        $this->delete('/contract_modules/' . $module->id)->assertRedirect();
        $this->assertDatabaseMissing('contract_modules', ['id' => $module->id]);
    }

    #[Test]
    public function document_request_store_requires_create_permission(): void
    {
        $this->actingAs(User::factory()->create());
        $this->post(route('document-requests.store'), [])->assertForbidden();
    }

    // ---------- Systemeinstellungen ----------

    #[Test]
    public function settings_pages_and_mutations_require_their_settings_permission(): void
    {
        $this->actingAs(User::factory()->create());

        $this->post(route('task_templates.store'), [])->assertForbidden();
        $this->get(route('tool.file-settings.index'))->assertForbidden();
        $this->get(route('tab.index'))->assertForbidden();
        $this->get(route('component.index'))->assertForbidden();
        $this->get(route('project-management-builder.index'))->assertForbidden();
        $this->get(route('project-print-layout.index'))->assertForbidden();
        $this->get(route('permission-presets.index'))->assertForbidden();

        // bewusst offen: Tab-Liste für die To-do-Erstellung
        $this->get(route('tab.list'))->assertSuccessful();
    }

    // ---------- Planungskalender ----------

    #[Test]
    public function planning_calendar_page_requires_planning_permission(): void
    {
        $this->actingAs(User::factory()->create());
        $this->get(route('planning-event-calendar.index'))->assertForbidden();
    }

    // ---------- Budget ----------

    #[Test]
    public function budget_lock_column_requires_verified_states_permission_even_for_global_budget_admin(): void
    {
        $this->actingAsUserWith(PermissionEnum::GLOBAL_PROJECT_BUDGET_ADMIN->value);
        $this->patch(route('project.budget.lock.column'), [])->assertForbidden();
    }

    #[Test]
    public function inventory_trash_requires_delete_permission(): void
    {
        $this->actingAs(User::factory()->create());
        $this->get(route('inventory.articles.trash'))->assertForbidden();
    }

    // ---------- Policies ----------

    #[Test]
    public function checklist_update_is_not_open_to_everyone_anymore(): void
    {
        $owner = User::factory()->create();
        $checklist = Checklist::factory()->create(['user_id' => $owner->id]);
        $stranger = User::factory()->create();

        $this->assertFalse($stranger->can('update', $checklist));
        $this->assertFalse($stranger->can('view', $checklist));
        $this->assertTrue($owner->can('update', $checklist));

        $this->actingAsUserWith(PermissionEnum::CHECKLIST_EDIT_PERMISSION->value, $stranger);
        $this->assertTrue($stranger->fresh()->can('update', $checklist));
        $this->assertTrue($stranger->fresh()->can('view', $checklist));
    }

    #[Test]
    public function project_team_delete_permission_is_honored_by_policy(): void
    {
        $project = Project::factory()->create();
        $user = User::factory()->create();

        $this->assertFalse($user->can('delete', $project));

        $project->users()->attach($user->id, ['delete_permission' => true]);
        $this->assertTrue($user->fresh()->can('delete', $project));
    }

    // ---------- Modul-Middleware ----------

    #[Test]
    public function module_middleware_matches_sub_paths_by_prefix(): void
    {
        $this->assertSame('projects', ModuleSettingsMiddleware::resolveSetting('/projects'));
        // Projekt-Unterseiten/-APIs werden von Kalender, Dienstplan und Budget genutzt: nur exakt (s. ReleaseHardeningTest)
        $this->assertNull(ModuleSettingsMiddleware::resolveSetting('/projects/5/tabs/3'));
        $this->assertSame('inventory', ModuleSettingsMiddleware::resolveSetting('/inventory-management/article/planning'));
        $this->assertSame('inventory', ModuleSettingsMiddleware::resolveSetting('/inventory/articles/trash'));
        $this->assertSame('crm', ModuleSettingsMiddleware::resolveSetting('/crm/contacts/7'));
        $this->assertSame('users', ModuleSettingsMiddleware::resolveSetting('/users'));
        // eigenes Profil / Einsatzplan bleiben ohne Personal-Modul erreichbar
        $this->assertNull(ModuleSettingsMiddleware::resolveSetting('/users/7/shiftplan'));
        $this->assertSame('shift_plan', ModuleSettingsMiddleware::resolveSetting('/shifts/view'));
        $this->assertNull(ModuleSettingsMiddleware::resolveSetting('/shifts/1'));
        $this->assertNull(ModuleSettingsMiddleware::resolveSetting('/usersearch'));
        $this->assertNull(ModuleSettingsMiddleware::resolveSetting('/dashboard'));
    }
}
