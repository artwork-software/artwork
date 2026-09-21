<?php

namespace Tests\Feature;

use Artwork\Modules\Budget\Models\BudgetManagementAccount;
use Artwork\Modules\Crm\Models\CrmContactType;
use Artwork\Modules\Event\Models\Event;
use Artwork\Modules\ExternalAccess\Enums\ExternalSubmissionContext;
use Artwork\Modules\ExternalAccess\Enums\ExternalSubmissionStatus;
use Artwork\Modules\ExternalAccess\Models\ExternalAccess;
use Artwork\Modules\ExternalAccess\Models\ExternalPendingSubmission;
use Artwork\Modules\Permission\Enums\PermissionEnum;
use Artwork\Modules\Project\Models\LinkListTemplate;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\Project\Models\ProjectTab;
use Artwork\Modules\Shift\Models\ShiftPresetTimeline;
use Artwork\Modules\User\Models\User;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\Test;

/**
 * Routen, die globale Einstellungen oder fremde Daten berühren, bleiben Nutzern ohne Rechte verschlossen
 * (Ergänzung zur AuthorizationMatrixTest).
 */
final class SecurityAuditDynamicRegressionTest extends FeatureTestCase
{
    // ---------- Dusk-Login (/_dusk/login/{userId})

    #[Test]
    public function dusk_login_routes_do_not_exist(): void
    {
        $this->assertFalse(Route::has('dusk.login'));
        $this->assertFalse(Route::has('dusk.logout'));
        $this->assertFalse(Route::has('dusk.user'));

        $victim = $this->adminUser();
        $this->get('/_dusk/login/' . $victim->id)->assertNotFound();
        $this->assertGuest();
    }

    // ---------- Sidebar-Konfiguration der Projekt-Tabs = Projekt-Einstellung

    #[Test]
    public function project_tab_sidebar_configuration_requires_project_settings_permission(): void
    {
        $tab = ProjectTab::factory()->create();
        $sidebarTab = $tab->sidebarTabs()->create(['name' => 'Seitenleiste', 'order' => 1]);

        $this->actingAsUserWith([]);
        $this->post(route('tab.sidebar.store', $tab), ['name' => 'Neu'])->assertForbidden();
        $this->delete(route('sidebar.tab.destroy', $sidebarTab))->assertForbidden();
        $this->assertDatabaseHas('project_tab_sidebar_tabs', ['id' => $sidebarTab->id]);

        $this->actingAsUserWith(PermissionEnum::PROJECT_SETTINGS_UPDATE->value);
        $this->post(route('tab.sidebar.store', $tab), ['name' => 'Neu'])->assertRedirect();
        $this->delete(route('sidebar.tab.destroy', $sidebarTab))->assertRedirect();
        $this->assertDatabaseMissing('project_tab_sidebar_tabs', ['id' => $sidebarTab->id]);
    }

    // ---------- Zeitleisten-Vorlagen an fremden Terminen

    #[Test]
    public function timeline_preset_import_requires_timeline_edit_right_on_the_event(): void
    {
        $event = Event::factory()->create();
        $preset = ShiftPresetTimeline::create(['name' => 'Vorlage']);
        $preset->times()->create(['start' => '10:00', 'end' => '11:00', 'description' => 'Aufbau']);

        $this->actingAsUserWith([]);
        $this->post(route('timeline-preset.import', [$event, $preset]))->assertForbidden();
        $this->assertDatabaseCount('timelines', 0);

        $this->actingAsUserWith(PermissionEnum::SHIFT_PLANNER->value);
        $this->post(route('timeline-preset.import', [$event, $preset]))->assertSuccessful();
        $this->assertDatabaseCount('timelines', 1);
    }

    #[Test]
    public function storing_a_timeline_preset_from_an_event_requires_preset_permission(): void
    {
        $event = Event::factory()->create();

        $this->actingAsUserWith([]);
        $this->post(route('timeline-preset.store.form.event', $event), ['name' => 'Aus Termin'])->assertForbidden();

        $this->actingAsUserWith(PermissionEnum::SHIFT_PLANNER->value);
        $this->post(route('timeline-preset.store.form.event', $event), ['name' => 'Aus Termin'])->assertSuccessful();
        $this->assertDatabaseHas('shift_preset_timelines', ['name' => 'Aus Termin']);
    }

    #[Test]
    public function timeline_preset_settings_page_requires_permission(): void
    {
        $this->actingAsUserWith([]);
        $this->get(route('shifts.timeline-presets.index'))->assertForbidden();

        $this->actingAsUserWith(PermissionEnum::EVENT_SETTINGS_UPDATE->value);
        $this->get(route('shifts.timeline-presets.index'))->assertOk();
    }

    // ---------- Link-Listen-Vorlagen (global): ändern/löschen nur Ersteller*in oder Projekt-Einstellungen

    #[Test]
    public function link_list_templates_can_only_be_changed_by_creator_or_settings_permission(): void
    {
        $owner = User::factory()->create();
        $template = LinkListTemplate::create([
            'name' => 'Links',
            'entries' => [['display' => 'x']],
            'created_by' => $owner->id,
        ]);
        $payload = ['name' => 'Geändert', 'entries' => [['display' => 'y']]];

        $this->actingAsUserWith([]);
        $this->patch(route('link_list_templates.update', $template), $payload)->assertForbidden();
        $this->delete(route('link_list_templates.destroy', $template))->assertForbidden();

        $this->actingAs($owner);
        $this->patch(route('link_list_templates.update', $template), $payload)->assertOk();

        $this->actingAsUserWith(PermissionEnum::PROJECT_SETTINGS_UPDATE->value);
        $this->delete(route('link_list_templates.destroy', $template))->assertNoContent();
        $this->assertDatabaseMissing('link_list_templates', ['id' => $template->id]);
    }

    // ---------- Benachrichtigungen: nur eigene löschen

    #[Test]
    public function deleting_notifications_by_key_only_touches_own_notifications(): void
    {
        $other = User::factory()->create();
        $me = $this->actingAsUserWith([]);
        foreach ([$other, $me] as $user) {
            $user->notifications()->create([
                'id' => (string) Str::uuid(),
                'type' => 'test',
                'data' => ['notificationKey' => 'key-1'],
            ]);
        }

        $this->post(route('event.notification.delete', ['notificationKey' => 'key-1']))->assertSuccessful();

        $this->assertSame(0, $me->notifications()->count());
        $this->assertSame(1, $other->notifications()->count());
    }

    // ---------- Externe CRM-Einreichungen: einsehen nur Einladende*r oder Admin

    #[Test]
    public function external_submission_review_page_is_limited_to_inviter_and_admins(): void
    {
        CrmContactType::query()->firstOrCreate(['slug' => 'freelancer'], ['name' => 'Freelancer']);
        $inviter = User::factory()->create();
        $external = ExternalAccess::factory()->create(['invited_by_user_id' => $inviter->id]);
        $submission = ExternalPendingSubmission::create([
            'external_access_id' => $external->id,
            'context' => ExternalSubmissionContext::CRM_SELF,
            'status' => ExternalSubmissionStatus::PENDING,
            'submitted_at' => now(),
        ]);
        $contact = $external->crmContact;

        $this->actingAsUserWith([PermissionEnum::CRM_VIEW->value]);
        $this->get(route('crm.contacts.external-submissions.show', [$contact, $submission]))->assertForbidden();
        $this->get(route('crm.contacts.external-submissions.index', $contact))->assertForbidden();

        $this->actingAs($inviter);
        $this->get(route('crm.contacts.external-submissions.show', [$contact, $submission]))->assertOk();

        $this->actingAsAdmin();
        $this->get(route('crm.contacts.external-submissions.index', $contact))->assertRedirect();
    }

    // ---------- Budget-Stammdaten-Suche: nur mit Budgetzugriff

    #[Test]
    public function budget_account_search_requires_budget_access(): void
    {
        BudgetManagementAccount::factory()->create(['account_number' => '4711', 'title' => 'Konto']);

        $this->actingAsUserWith([]);
        $this->get(route('budget-settings.account-management.search-accounts', ['search' => '4711']))
            ->assertForbidden();
        $this->get(route('budget-settings.account-management.search-cost-units', ['search' => 'x']))->assertForbidden();

        $member = $this->actingAsUserWith([]);
        Project::factory()->create()->users()->attach($member->id, ['access_budget' => true]);
        $this->get(route('budget-settings.account-management.search-accounts', ['search' => '4711']))->assertOk();
        $this->getJson(route('budget-settings.account-management.search-accounts'))->assertUnprocessable();
    }

    // ---------- Checklisten-Filter nur am eigenen Account

    #[Test]
    public function checklist_filter_cannot_be_changed_for_other_users(): void
    {
        $other = User::factory()->create(['checklist_has_projects' => false]);

        $this->actingAsUserWith([]);
        $this->patch(route('user.update.checklist.filter', $other), ['checklist_has_projects' => true])
            ->assertForbidden();
        $this->assertFalse($other->fresh()->checklist_has_projects);
    }
}
