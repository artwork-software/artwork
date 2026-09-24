<?php

namespace Tests\Feature;

use Artwork\Modules\Budget\Models\Column;
use Artwork\Modules\Budget\Models\ColumnCell;
use Artwork\Modules\Budget\Models\MainPosition;
use Artwork\Modules\Budget\Models\SageNotAssignedData;
use Artwork\Modules\Budget\Models\SubPosition;
use Artwork\Modules\Budget\Models\SubPositionRow;
use Artwork\Modules\Budget\Models\Table;
use Artwork\Modules\Crm\Models\CrmContact;
use Artwork\Modules\Crm\Models\CrmContactType;
use Artwork\Modules\Event\Models\Event;
use Artwork\Modules\Event\Repositories\EventRepository;
use Artwork\Modules\Freelancer\Http\Resources\FreelancerShowResource;
use Artwork\Modules\Freelancer\Models\Freelancer;
use Artwork\Modules\Permission\Enums\PermissionEnum;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\Project\Models\ProjectPrintLayout;
use Artwork\Modules\Room\Models\Room;
use Artwork\Modules\Room\Models\RoomFile;
use Artwork\Modules\ServiceProvider\Models\ServiceProvider;
use Artwork\Modules\User\Models\User;
use Artwork\Modules\User\Models\UserFilterTemplate;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia;
use PHPUnit\Framework\Attributes\Test;

/**
 * Regressionen zum Autorisierungs-Audit 23.09.2026 (docs/security/audit-2026-09-23.md, F-01 bis F-12).
 */
final class SecurityAuditPatternRegressionTest extends FeatureTestCase
{
    // F-01: Gage und Notiz von Freelancern/Dienstleistern nie roh serialisieren

    #[Test]
    public function freelancer_and_service_provider_serialization_hides_salary_and_note(): void
    {
        $freelancer = Freelancer::factory()->create([
            'salary_per_hour' => 55,
            'salary_description' => 'Tagessatz',
            'note' => 'intern',
        ]);
        $serviceProvider = ServiceProvider::factory()->create([
            'salary_per_hour' => 70,
            'salary_description' => 'Pauschale',
            'note' => 'intern',
        ]);

        foreach ([$freelancer->fresh()->toArray(), $serviceProvider->fresh()->toArray()] as $array) {
            $this->assertArrayNotHasKey('salary_per_hour', $array);
            $this->assertArrayNotHasKey('salary_description', $array);
            $this->assertArrayNotHasKey('note', $array);
            $this->assertArrayHasKey('email', $array);
        }

        // Profilseite gibt die Felder weiterhin explizit aus.
        $resource = FreelancerShowResource::make($freelancer->fresh())->resolve();
        $this->assertSame('Tagessatz', $resource['salary_description']);
        $this->assertSame('intern', $resource['note']);
    }

    #[Test]
    public function users_page_does_not_expose_freelancer_salary(): void
    {
        Freelancer::factory()->create(['salary_per_hour' => 55, 'salary_description' => 'Tagessatz']);
        $this->actingAsUserWith([]);

        $this->get(route('users'))
            ->assertOk()
            ->assertInertia(function (AssertableInertia $page): void {
                $page->has('freelancers.0', function (AssertableInertia $freelancer): void {
                    $freelancer->missing('salary_per_hour')
                        ->missing('salary_description')
                        ->missing('note')
                        ->etc();
                });
            });
    }

    // F-03: private E-Mail/Telefon serverseitig ausblenden

    #[Test]
    public function private_contact_data_is_hidden_from_other_users_without_permission(): void
    {
        $subject = User::factory()->create([
            'email_private' => true,
            'phone_private' => true,
            'phone_number' => '0123',
        ]);

        $viewer = $this->actingAsUserWith([]);
        $array = $subject->fresh()->toArray();
        $this->assertNull($array['email']);
        $this->assertNull($array['phone_number']);
        $this->assertNull($subject->visibleEmailFor($viewer));

        $this->actingAs($subject);
        $this->assertSame($subject->email, $subject->fresh()->toArray()['email']);

        $this->actingAsUserWith([PermissionEnum::CAN_VIEW_PRIVATE_USER_INFO->value]);
        $this->assertSame('0123', $subject->fresh()->toArray()['phone_number']);
    }

    #[Test]
    public function public_contact_data_stays_visible(): void
    {
        $subject = User::factory()->create(['email_private' => false, 'phone_private' => false]);
        $this->actingAsUserWith([]);

        $this->assertSame($subject->email, $subject->fresh()->toArray()['email']);
    }

    #[Test]
    public function team_search_masks_private_email(): void
    {
        $subject = User::factory()->create([
            'first_name' => 'Privatina',
            'email_private' => true,
        ]);
        $this->actingAsUserWith([]);

        $users = $this->getJson(route('users_departments.search', ['query' => 'Privatina']))
            ->assertOk()
            ->json('users');

        $found = collect($users)->firstWhere('id', $subject->id);
        $this->assertNotNull($found);
        $this->assertNull($found['email']);
    }

    // F-02: Sage-Buchungen nur mit Sage-Recht ins Budget übernehmen

    #[Test]
    public function budget_user_without_sage_permission_cannot_move_global_sage_booking(): void
    {
        [$project, $cell] = $this->budgetCell();
        $sageData = $this->sageNotAssignedData(null);
        $user = $this->actingAsUserWith([]);
        $project->users()->attach($user->id, ['access_budget' => true]);

        $this->post(route('project.budget.move.sage', [
            'sageNotAssignedData' => $sageData->id,
            'columnCell' => $cell->id,
        ]))->assertForbidden();

        $this->post(route('project.budget.drop.sage'), [
            'table_id' => $cell->column->table_id,
            'sub_position_id' => $cell->subPositionRow->sub_position_id,
            'positionBefore' => -1,
            'sage_data_id' => $sageData->id,
        ])->assertForbidden();

        $this->assertNotSoftDeleted($sageData);
    }

    #[Test]
    public function sage_assignment_policy_mirrors_budget_visibility(): void
    {
        [$project] = $this->budgetCell();
        $global = $this->sageNotAssignedData(null);
        $own = $this->sageNotAssignedData($project->id);
        $foreign = $this->sageNotAssignedData(Project::factory()->create()->id);

        $globalViewer = $this->actingAsUserWith([PermissionEnum::VIEW_GLOBAL_SAGE_DATA->value]);
        $this->assertTrue(Gate::forUser($globalViewer)->allows('assignToProject', [$global, $project->id]));
        $this->assertFalse(Gate::forUser($globalViewer)->allows('assignToProject', [$own, $project->id]));

        $projectViewer = $this->actingAsUserWith([PermissionEnum::VIEW_PROJECT_SAGE_DATA->value]);
        $this->assertTrue(Gate::forUser($projectViewer)->allows('assignToProject', [$own, $project->id]));
        $this->assertFalse(Gate::forUser($projectViewer)->allows('assignToProject', [$foreign, $project->id]));
        $this->assertFalse(Gate::forUser($projectViewer)->allows('assignToProject', [$global, $project->id]));
    }

    // F-04 / F-06 / F-07: Planungstermine nur mit Planungskalender-Recht

    #[Test]
    public function planning_calendar_api_requires_planning_permission(): void
    {
        $params = ['start_date' => '2026-04-01', 'end_date' => '2026-04-02', 'isPlanning' => 'true'];

        $this->actingAsUserWith([]);
        $this->getJson(route('events.all', $params))->assertForbidden();
        $this->getJson(route('events.all', array_merge($params, ['isPlanning' => 'false'])))->assertOk();

        $this->actingAsUserWith([PermissionEnum::CAN_SEE_PLANNING_CALENDAR->value]);
        $this->getJson(route('events.all', $params))->assertOk();
    }

    #[Test]
    public function room_events_endpoint_hides_planning_events_without_permission(): void
    {
        $room = Room::factory()->create();
        $fixed = Event::factory()->create([
            'room_id' => $room->id,
            'eventName' => 'FixerTermin',
            'is_planning' => false,
            'start_time' => '2026-04-10 10:00:00',
            'end_time' => '2026-04-10 12:00:00',
        ]);
        $planned = Event::factory()->create([
            'room_id' => $room->id,
            'eventName' => 'GeplanterTermin',
            'is_planning' => true,
            'start_time' => '2026-04-10 13:00:00',
            'end_time' => '2026-04-10 14:00:00',
        ]);
        $query = ['rooms' => [$room->id], 'days' => ['2026-04-10']];

        $this->actingAsUserWith([]);
        $content = $this->getJson(route('events.for-rooms-by-days-and-project', $query))->assertOk()->getContent();
        $this->assertStringContainsString('FixerTermin', $content);
        $this->assertStringNotContainsString('GeplanterTermin', $content);

        $this->actingAsUserWith([PermissionEnum::CAN_SEE_PLANNING_CALENDAR->value]);
        $content = $this->getJson(route('events.for-rooms-by-days-and-project', $query))->assertOk()->getContent();
        $this->assertStringContainsString('GeplanterTermin', $content);

        $this->assertNotNull($fixed->id);
        $this->assertNotNull($planned->id);
    }

    #[Test]
    public function export_without_display_settings_excludes_planning_events_for_non_planners(): void
    {
        $project = Project::factory()->create();
        Event::factory()->create(['project_id' => $project->id, 'is_planning' => false]);
        $planned = Event::factory()->create(['project_id' => $project->id, 'is_planning' => true]);
        $configuration = collect([
            'desiresTimespanExport' => false,
            'conditional' => ['projects' => [$project->id]],
            'filter' => [],
        ]);

        $this->actingAsUserWith([]);
        $ids = app(EventRepository::class)->getEventsForExport($configuration)->pluck('id');
        $this->assertCount(1, $ids);
        $this->assertNotContains($planned->id, $ids);

        $this->actingAsUserWith([PermissionEnum::CAN_EDIT_PLANNING_CALENDAR->value]);
        $ids = app(EventRepository::class)->getEventsForExport($configuration)->pluck('id');
        $this->assertContains($planned->id, $ids);
    }

    // F-05: CRM-Stammdaten

    #[Test]
    public function crm_viewer_can_rename_but_not_deactivate_or_set_image_path(): void
    {
        $contact = $this->crmContact();
        $this->actingAsUserWith([PermissionEnum::CRM_VIEW->value]);

        $this->patch(route('crm.contacts.update', $contact), ['display_name' => 'Neuer Name'])
            ->assertRedirect();
        $this->assertSame('Neuer Name', $contact->fresh()->display_name);

        $this->patch(route('crm.contacts.update', $contact), ['is_active' => false])->assertForbidden();
        $this->assertTrue((bool) $contact->fresh()->is_active);

        $this->patch(route('crm.contacts.update', $contact), ['profile_image' => '../../.env'])->assertRedirect();
        $this->assertNull($contact->fresh()->profile_image);
    }

    #[Test]
    public function crm_manager_can_deactivate_contact(): void
    {
        $contact = $this->crmContact();
        $this->actingAsUserWith([PermissionEnum::CRM_VIEW->value, PermissionEnum::CRM_MANAGER->value]);

        $this->patch(route('crm.contacts.update', $contact), ['is_active' => false])->assertRedirect();
        $this->assertFalse((bool) $contact->fresh()->is_active);
    }

    // F-08: Team-Suche nur mit Projekt-Sichtrecht

    #[Test]
    public function project_user_search_requires_project_view_and_valid_project(): void
    {
        $project = Project::factory()->create();
        $this->actingAsUserWith([]);

        $this->getJson(route('project.user.search', ['query' => 'x', 'projectId' => $project->id]))
            ->assertForbidden();
        $this->getJson(route('project.user.search', ['query' => 'x']))->assertUnprocessable();
    }

    // F-09: fremde Filtervorlagen

    #[Test]
    public function foreign_filter_template_cannot_be_activated(): void
    {
        $owner = User::factory()->create();
        $template = UserFilterTemplate::query()->create([
            'name' => 'Fremde Vorlage',
            'user_id' => $owner->id,
            'filter_type' => 'calendar_filter',
        ]);
        $user = $this->actingAsUserWith([]);

        $this->post(route('filter.activate', ['filter' => $template->id, 'user' => $user->id]))
            ->assertForbidden();
    }

    // F-10: Benachrichtigungsbild auf der public-Disk nur als Bild

    #[Test]
    public function global_notification_rejects_non_image_upload(): void
    {
        $this->actingAsAdmin();

        $this->put(route('global_notification.store'), [
            'notificationName' => 'Hinweis',
            'notificationImage' => UploadedFile::fake()->create('liste.csv', 2, 'text/csv'),
        ])->assertSessionHasErrors('notificationImage');
    }

    // F-11: Drucklayout nur mit validierten Feldern

    #[Test]
    public function print_layout_store_ignores_unvalidated_fields(): void
    {
        $this->actingAsAdmin();

        $this->post(route('project-print-layout.store'), [
            'name' => 'Layout',
            'columns_header' => 1,
            'columns_footer' => 1,
            'columns_body' => 1,
            'is_active' => true,
            'is_default' => true,
            'notes' => 'eingeschleust',
        ])->assertOk();

        $layout = ProjectPrintLayout::query()->where('name', 'Layout')->firstOrFail();
        $this->assertFalse((bool) $layout->is_default);
        $this->assertNotSame('eingeschleust', $layout->notes);
    }

    // F-12: Raumdokumente am Raum autorisieren

    #[Test]
    public function room_files_follow_room_access(): void
    {
        $room = Room::factory()->create();
        $roomFile = RoomFile::factory()->create(['room_id' => $room->id]);
        Storage::disk('local')->put('room_files/' . $roomFile->basename, 'inhalt');

        $this->actingAsUserWith([PermissionEnum::PROJECT_VIEW->value]);
        $this->get(route('download_room_file', $roomFile))->assertForbidden();

        $roomAdmin = $this->actingAsUserWith([]);
        $room->users()->attach($roomAdmin->id, ['is_admin' => true, 'can_request' => false]);
        $this->get(route('download_room_file', $roomFile))->assertOk();
    }

    // Zeitleisten-Vorlagen: gleiche Schranke wie der Import in den Termin

    #[Test]
    public function timeline_preset_list_requires_timeline_edit_right_on_event(): void
    {
        $event = Event::factory()->create();

        $this->actingAsUserWith([]);
        $this->getJson(route('timeline-presets.all', ['event' => $event->id]))->assertForbidden();
        $this->getJson(route('timeline-presets.all'))->assertNotFound();

        $this->actingAsUserWith([PermissionEnum::SHIFT_PLANNER->value]);
        $this->getJson(route('timeline-presets.all', ['event' => $event->id]))->assertOk();
    }

    /**
     * @return array{0: Project, 1: ColumnCell}
     */
    private function budgetCell(): array
    {
        $project = Project::factory()->create();
        $table = Table::factory()->create(['project_id' => $project->id, 'is_template' => false]);
        $column = Column::factory()->create(['table_id' => $table->id]);
        $mainPosition = MainPosition::factory()->create(['table_id' => $table->id]);
        $subPosition = SubPosition::factory()->create(['main_position_id' => $mainPosition->id]);
        $row = SubPositionRow::factory()->create(['sub_position_id' => $subPosition->id]);
        $cell = ColumnCell::factory()->create(['column_id' => $column->id, 'sub_position_row_id' => $row->id]);

        return [$project, $cell];
    }

    private function sageNotAssignedData(?int $projectId): SageNotAssignedData
    {
        return SageNotAssignedData::query()->create([
            'project_id' => $projectId,
            'sage_id' => random_int(1, 999999),
            'tan' => 1,
            'periode' => 1,
            'kto_haben' => '1000',
            'kreditor' => 'Kreditor',
            'buchungstext' => 'Buchung',
            'buchungsbetrag' => 12.5,
            'belegnummer' => 'B1',
            'belegdatum' => '2026-01-01',
            'kto_soll' => '2000',
            'sa_kto' => '3000',
            'kst_traeger' => 'T',
            'kst_stelle' => 'S',
            'buchungsdatum' => '2026-01-01',
        ]);
    }

    private function crmContact(): CrmContact
    {
        $type = CrmContactType::withTrashed()->firstOrCreate(
            ['slug' => 'audit-pattern-type'],
            ['name' => 'Audit', 'is_system' => false, 'is_active' => true]
        );

        return CrmContact::query()->create([
            'crm_contact_type_id' => $type->id,
            'display_name' => 'Alter Name',
            'is_active' => true,
        ]);
    }
}
