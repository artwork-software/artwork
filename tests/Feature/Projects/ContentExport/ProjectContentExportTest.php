<?php

namespace Tests\Feature\Projects\ContentExport;

use Artwork\Modules\Category\Models\Category;
use Artwork\Modules\Department\Models\Department;
use Artwork\Modules\Event\Models\Event;
use Artwork\Modules\EventType\Models\EventType;
use Artwork\Modules\Permission\Enums\PermissionEnum;
use Artwork\Modules\Project\Enum\ProjectTabComponentEnum;
use Artwork\Modules\Project\Models\Component;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\Project\Models\ProjectComponentValue;
use Artwork\Modules\Project\Models\ProjectState;
use Artwork\Modules\Project\Policies\ProjectPolicy;
use Artwork\Modules\Room\Models\Room;
use Artwork\Modules\User\Models\User;
use Carbon\Carbon;
use Illuminate\Testing\TestResponse;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Activitylog\Models\Activity;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Tests\Feature\FeatureTestCase;
use ZipArchive;

/**
 * Projektinhalte-Export (Excel/Word) aus der Projektübersicht: Recht, Projektzugriff, Komponenten-Sichtbarkeit,
 * Projektauswahl, Dateiinhalt und Verlauf.
 */
final class ProjectContentExportTest extends FeatureTestCase
{
    private const EXPORT = PermissionEnum::PROJECT_OVERVIEW_EXPORT->value;

    private Component $textArea;

    private Component $checkbox;

    private Component $dropdown;

    private Component $linkList;

    private Component $restricted;

    private Project $teamProject;

    private Project $foreignProject;

    private User $member;

    private EventType $performance;

    private EventType $setup;

    private Room $hall;

    protected function setUp(): void
    {
        parent::setUp();

        $this->textArea = $this->createComponent('Short text', ProjectTabComponentEnum::TEXT_AREA);
        $this->checkbox = $this->createComponent('Accessible', ProjectTabComponentEnum::CHECKBOX, ['checked' => false]);
        $this->dropdown = $this->createComponent('Language', ProjectTabComponentEnum::DROPDOWN);
        $this->linkList = $this->createComponent('Press', ProjectTabComponentEnum::LINK_LIST);
        $this->restricted = $this->createComponent('Secret', ProjectTabComponentEnum::TEXT_FIELD, [], 'someSeeSomeEdit');

        $this->performance = EventType::factory()->create(['name' => 'Performance']);
        $this->setup = EventType::factory()->create(['name' => 'Setup']);
        $this->hall = Room::factory()->create(['name' => 'Hall']);

        $this->teamProject = Project::factory()->create(['name' => 'Team project']);
        $this->foreignProject = Project::factory()->create(['name' => 'Foreign project']);

        $this->member = User::factory()->create();
        $this->teamProject->users()->attach($this->member->id);

        $this->value($this->teamProject, $this->textArea, ['text' => "Line one\r\nLine two"]);
        $this->value($this->teamProject, $this->checkbox, ['checked' => true]);
        $this->value($this->teamProject, $this->dropdown, ['selected' => ['German', 'English']]);
        $this->value($this->teamProject, $this->linkList, ['links' => [
            ['label' => 'Photos', 'url' => 'https://example.test/photos'],
            ['label' => '', 'url' => 'https://example.test/text'],
        ]]);
        $this->value($this->teamProject, $this->restricted, ['text' => 'RESTRICTED-VALUE']);
        $this->value($this->foreignProject, $this->textArea, ['text' => 'FOREIGN-VALUE']);

        $this->event($this->teamProject, $this->performance, '2026-02-04 20:00', '2026-02-04 21:30');
        $this->event($this->foreignProject, $this->performance, '2026-02-01 20:00', '2026-02-01 22:00');
    }

    #[Test]
    public function routes_require_the_overview_export_permission(): void
    {
        $this->actingAs($this->member);

        $this->getJson(route('projects.content-export.options'))->assertForbidden();
        $this->postJson(route('projects.content-export.cache'), $this->payload())->assertForbidden();
        $this->getJson(route('projects.content-export.templates.index'))->assertForbidden();
        $this->get(route('projects.export.project-role-matrix', [
            'start_date' => '2026-01-01',
            'end_date' => '2026-01-31',
        ]))->assertForbidden();
    }

    #[Test]
    public function role_matrix_additionally_needs_read_all_projects(): void
    {
        $this->actingAsUserWith(self::EXPORT, $this->member);

        $this->get(route('projects.export.project-role-matrix', [
            'start_date' => '2026-01-01',
            'end_date' => '2026-01-31',
        ]))->assertForbidden();
    }

    #[Test]
    public function team_member_exports_only_enterable_projects_and_visible_components(): void
    {
        $this->actingAsUserWith(self::EXPORT, $this->member);

        $columns = [
            ['key' => 'system:project_name'],
            ['key' => 'component:' . $this->textArea->id],
            ['key' => 'component:' . $this->restricted->id],
        ];

        $this->postJson(route('projects.content-export.preview'), ['columns' => $columns])
            ->assertOk()
            ->assertExactJson(['count' => 1, 'hidden_columns' => 1]);

        $sheet = $this->excel($this->payload(['columns' => $columns]));

        $this->assertSame([['Project name', 'Short text'], ['Team project', "Line one\nLine two"]], $sheet);
    }

    #[Test]
    public function options_list_only_visible_components(): void
    {
        $this->actingAsUserWith(self::EXPORT, $this->member);

        $keys = collect($this->getJson(route('projects.content-export.options'))->assertOk()->json('columns'))
            ->pluck('key');

        $this->assertContains('component:' . $this->textArea->id, $keys);
        $this->assertContains('system:events', $keys);
        $this->assertNotContains('component:' . $this->restricted->id, $keys);
    }

    #[Test]
    public function department_membership_grants_access_like_the_policy(): void
    {
        $departmentUser = User::factory()->create();
        $department = Department::factory()->create();
        $department->users()->attach($departmentUser->id);
        $this->foreignProject->departments()->attach($department->id);

        $enterable = Project::query()->enterableBy($departmentUser)->pluck('id');
        $policy = app(ProjectPolicy::class);

        foreach ([$this->teamProject, $this->foreignProject] as $project) {
            $this->assertSame(
                $policy->view($departmentUser, $project),
                $enterable->contains($project->id),
                $project->name
            );
        }
        $this->assertTrue($enterable->contains($this->foreignProject->id));
    }

    #[Test]
    public function excel_contains_formatted_values_sorted_by_first_event(): void
    {
        $this->actingAsUserWith([self::EXPORT, PermissionEnum::PROJECT_VIEW->value]);
        $this->value($this->foreignProject, $this->textArea, ['text' => '=HYPERLINK("http://evil.test")']);
        $this->event($this->teamProject, $this->setup, '2026-02-03 10:00', '2026-02-03 18:00', allDay: true);

        $sheet = $this->excel($this->payload(['columns' => [
            ['key' => 'system:project_name', 'title' => 'Title'],
            ['key' => 'system:events'],
            ['key' => 'system:rooms'],
            ['key' => 'component:' . $this->textArea->id],
            ['key' => 'component:' . $this->checkbox->id],
            ['key' => 'component:' . $this->dropdown->id],
            ['key' => 'component:' . $this->linkList->id],
        ]]));

        $this->assertSame(['Title', 'Events', 'Rooms', 'Short text', 'Accessible', 'Language', 'Press'], $sheet[0]);
        // Fremdprojekt hat den früheren Termin (01.02.) und steht zuerst
        $this->assertSame('Foreign project', $sheet[1][0]);
        $this->assertSame('=HYPERLINK("http://evil.test")', $sheet[1][3]);
        $this->assertSame('No', $sheet[1][4]);
        $this->assertSame('Team project', $sheet[2][0]);
        $this->assertSame(
            "Setup · Tue, 03.02.2026 · All day · Hall\nPerformance · Wed, 04.02.2026 · 20:00–21:30 · Hall",
            $sheet[2][1]
        );
        $this->assertSame('Hall', $sheet[2][2]);
        $this->assertSame('Yes', $sheet[2][4]);
        $this->assertSame("German\nEnglish", $sheet[2][5]);
        $this->assertSame("Photos: https://example.test/photos\nhttps://example.test/text", $sheet[2][6]);
    }

    #[Test]
    public function event_column_shows_only_the_chosen_event_types(): void
    {
        $this->actingAsUserWith(self::EXPORT, $this->member);
        $this->event($this->teamProject, $this->setup, '2026-02-03 10:00', '2026-02-03 18:00');

        $sheet = $this->excel($this->payload([
            'columns' => [['key' => 'system:events']],
            'event_column' => ['event_type_ids' => [$this->setup->id]],
        ]));

        $this->assertSame('Setup · Tue, 03.02.2026 · 10:00–18:00 · Hall', $sheet[1][0]);
    }

    #[Test]
    public function characteristics_are_combined_with_or_and_other_filters_with_and(): void
    {
        $this->actingAsUserWith([self::EXPORT, PermissionEnum::PROJECT_VIEW->value]);

        $state = ProjectState::factory()->create();
        $category = Category::factory()->create();
        $stateProject = Project::factory()->create(['name' => 'State project', 'state' => $state->id]);
        $categoryProject = Project::factory()->create(['name' => 'Category project']);
        $categoryProject->categories()->attach($category->id);
        $setupProject = Project::factory()->create(['name' => 'Setup project']);
        $this->event($setupProject, $this->setup, '2026-03-10 09:00', '2026-03-10 12:00');
        Project::factory()->create(['name' => 'Group', 'is_group' => true, 'state' => $state->id]);

        $names = $this->exportedNames(['filters' => [
            'project_state_ids' => [$state->id],
            'category_ids' => [$category->id],
            'event_type_ids' => [$this->setup->id],
        ]]);
        $this->assertEqualsCanonicalizing(['State project', 'Category project', 'Setup project'], $names);

        // Zeitraum UND Merkmal: nur der Aufbau-Termin im März passt
        $names = $this->exportedNames(['filters' => [
            'date_start' => '2026-03-01',
            'date_end' => '2026-03-31',
            'project_state_ids' => [$state->id],
            'event_type_ids' => [$this->setup->id],
        ]]);
        $this->assertSame(['Setup project'], $names);

        $stateProject->users()->attach($this->member->id);
        $names = $this->exportedNames(['filters' => ['member_ids' => [$this->member->id]]]);
        $this->assertEqualsCanonicalizing(['Team project', 'State project'], $names);
    }

    #[Test]
    public function project_group_filter_limits_to_group_members(): void
    {
        $this->actingAsUserWith([self::EXPORT, PermissionEnum::PROJECT_VIEW->value]);
        $group = Project::factory()->create(['name' => 'Festival', 'is_group' => true]);
        $group->projectsOfGroup()->attach($this->foreignProject->id);

        $this->assertSame(
            ['Foreign project'],
            $this->exportedNames(['filters' => ['project_group_id' => $group->id]])
        );
    }

    #[Test]
    public function date_range_needs_both_dates(): void
    {
        $this->actingAsUserWith(self::EXPORT, $this->member);

        $this->postJson(route('projects.content-export.cache'), $this->payload([
            'filters' => ['date_start' => '2026-03-01'],
        ]))->assertJsonValidationErrors('filters.date_end');
    }

    #[Test]
    public function word_export_writes_plain_paragraphs_with_labels(): void
    {
        $this->actingAsUserWith(self::EXPORT, $this->member);

        $xml = $this->wordXml($this->payload([
            'format' => 'docx',
            'columns' => [
                ['key' => 'system:project_name', 'show_label' => false],
                ['key' => 'component:' . $this->textArea->id, 'show_label' => true, 'title' => 'Text <for> web'],
                ['key' => 'component:' . $this->dropdown->id, 'show_label' => true],
            ],
        ]));

        $this->assertStringNotContainsString('<w:tbl', $xml);
        $this->assertStringNotContainsString('<w:tab', $xml);
        $this->assertStringContainsString('Team project', $xml);
        $this->assertStringContainsString('Text &lt;for&gt; web', $xml);
        $this->assertStringContainsString('Line one</w:t></w:r></w:p><w:p><w:pPr/><w:r><w:rPr/><w:t xml:space="preserve">Line two', $xml);
        $this->assertStringNotContainsString('<w:br/>', $xml);
        $this->assertStringContainsString('<w:b ', $xml);
        $this->assertStringNotContainsString('Foreign project', $xml);
    }

    #[Test]
    public function download_token_is_bound_to_its_creator_and_single_use(): void
    {
        $this->actingAsUserWith(self::EXPORT, $this->member);
        $token = $this->postJson(route('projects.content-export.cache'), $this->payload())->assertOk()->getContent();

        $this->actingAsUserWith(self::EXPORT);
        $this->get(route('projects.content-export.download', $token))->assertForbidden();

        $this->actingAs($this->member);
        $this->get(route('projects.content-export.download', $token))->assertStatus(410);
    }

    #[Test]
    public function export_is_logged_in_the_history_of_each_exported_project(): void
    {
        $this->actingAsUserWith(self::EXPORT, $this->member);

        $this->excel($this->payload());

        $activity = Activity::query()
            ->where('subject_type', $this->teamProject->getMorphClass())
            ->where('subject_id', $this->teamProject->id)
            ->where('event', 'exported')
            ->sole();
        $this->assertSame($this->member->id, (int) $activity->causer_id);
        $this->assertSame(
            ['Excel'],
            $activity->properties->first()['translationKeyPlaceholderValues']
        );
        $this->assertFalse(Activity::query()
            ->where('subject_id', $this->foreignProject->id)
            ->where('event', 'exported')
            ->exists());

        $this->getJson(route('projects.history', $this->teamProject))
            ->assertOk()
            ->assertJsonFragment(['translationKey' => 'Project contents were exported as {0}']);
    }

    /**
     * @param array<string, mixed> $overrides
     * @return array<string, mixed>
     */
    private function payload(array $overrides = []): array
    {
        return [
            'format' => 'xlsx',
            'columns' => [['key' => 'system:project_name']],
            'filters' => [],
            'event_column' => ['event_type_ids' => []],
            ...$overrides,
        ];
    }

    /**
     * @param array<string, mixed> $overrides
     * @return array<int, string>
     */
    private function exportedNames(array $overrides): array
    {
        $sheet = $this->excel($this->payload($overrides));

        return array_values(array_map(static fn (array $row): string => (string) $row[0], array_slice($sheet, 1)));
    }

    /**
     * @param array<string, mixed> $payload
     * @return array<int, array<int, string|null>>
     */
    private function excel(array $payload): array
    {
        $path = $this->download($payload);
        $rows = IOFactory::load($path)->getActiveSheet()->toArray(null, false, false);
        @unlink($path);

        return array_map(
            static fn (array $row): array => array_map(static fn ($cell) => $cell === null ? null : (string) $cell, $row),
            $rows
        );
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function wordXml(array $payload): string
    {
        $path = $this->download($payload);
        $zip = new ZipArchive();
        $this->assertTrue($zip->open($path));
        $xml = (string) $zip->getFromName('word/document.xml');
        $zip->close();
        @unlink($path);

        return $xml;
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function download(array $payload): string
    {
        $token = $this->postJson(route('projects.content-export.cache'), $payload)->assertOk()->getContent();
        /** @var TestResponse $response */
        $response = $this->get(route('projects.content-export.download', $token))->assertOk();
        $this->assertInstanceOf(BinaryFileResponse::class, $response->baseResponse);

        return $response->baseResponse->getFile()->getPathname();
    }

    /**
     * @param array<string, mixed> $data
     */
    private function createComponent(
        string $name,
        ProjectTabComponentEnum $type,
        array $data = [],
        string $permissionType = 'allSeeAndEdit'
    ): Component {
        return Component::create([
            'name' => $name,
            'type' => $type->value,
            'data' => $data,
            'special' => false,
            'permission_type' => $permissionType,
        ]);
    }

    /**
     * @param array<string, mixed> $data
     */
    private function value(Project $project, Component $component, array $data): void
    {
        ProjectComponentValue::query()->updateOrCreate(
            ['project_id' => $project->id, 'component_id' => $component->id],
            ['data' => $data],
        );
    }

    private function event(
        Project $project,
        EventType $type,
        string $start,
        string $end,
        bool $allDay = false
    ): Event {
        return Event::factory()->create([
            'project_id' => $project->id,
            'event_type_id' => $type->id,
            'room_id' => $this->hall->id,
            'start_time' => Carbon::parse($start),
            'end_time' => Carbon::parse($end),
            'allDay' => $allDay,
        ]);
    }
}
