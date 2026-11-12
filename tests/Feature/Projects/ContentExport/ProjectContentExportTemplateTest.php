<?php

namespace Tests\Feature\Projects\ContentExport;

use Artwork\Modules\Permission\Enums\PermissionEnum;
use Artwork\Modules\Project\Models\ProjectContentExportTemplate;
use Artwork\Modules\User\Models\User;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

/**
 * Vorlagen des Projektinhalte-Exports: für alle mit Exportrecht sichtbar, ändern/löschen nur anlegende Person
 * oder Admins, ohne Zeitraum und einzeln gewählte Projekte gespeichert.
 */
final class ProjectContentExportTemplateTest extends FeatureTestCase
{
    private const EXPORT = PermissionEnum::PROJECT_OVERVIEW_EXPORT->value;

    #[Test]
    public function template_is_stored_without_date_range_and_unknown_filters(): void
    {
        $user = $this->actingAsUserWith(self::EXPORT);
        $member = User::factory()->create(['first_name' => 'Ada', 'last_name' => 'Lovelace']);

        $response = $this->postJson(route('projects.content-export.templates.store'), [
            'name' => ' PSS ',
            'columns' => [
                ['key' => 'system:project_name', 'title' => '  ', 'show_label' => false],
                ['key' => 'component:5', 'title' => 'Category', 'show_label' => true],
            ],
            'settings' => [
                'filters' => [
                    'date_start' => '2026-01-01',
                    'date_end' => '2026-01-31',
                    'project_state_ids' => [3],
                    'member_ids' => [$member->id],
                    'project_ids' => [42],
                    'evil' => 'x',
                ],
                'event_column' => ['event_type_ids' => [7]],
            ],
        ])->assertOk();

        $template = ProjectContentExportTemplate::query()->findOrFail($response->json('id'));
        $this->assertSame('PSS', $template->name);
        $this->assertSame($user->id, $template->created_by);
        $this->assertSame([
            ['key' => 'system:project_name', 'title' => null, 'show_label' => false],
            ['key' => 'component:5', 'title' => 'Category', 'show_label' => true],
        ], $template->columns);
        $this->assertArrayNotHasKey('date_start', $template->settings['filters']);
        $this->assertArrayNotHasKey('evil', $template->settings['filters']);
        $this->assertArrayNotHasKey('project_ids', $template->settings['filters']);
        $this->assertSame([3], $template->settings['filters']['project_state_ids']);
        $this->assertSame([7], $template->settings['event_column']['event_type_ids']);
        $response->assertJsonPath('members.0.name', 'Ada Lovelace')->assertJsonPath('can_manage', true);
    }

    #[Test]
    public function invalid_column_keys_and_duplicate_names_are_rejected(): void
    {
        $this->actingAsUserWith(self::EXPORT);
        ProjectContentExportTemplate::factory()->create(['name' => 'Taken']);

        $this->postJson(route('projects.content-export.templates.store'), [
            'name' => 'Taken',
            'columns' => [['key' => 'system:budget'], ['key' => 'component:abc']],
        ])->assertJsonValidationErrors(['name', 'columns.0.key', 'columns.1.key']);
    }

    #[Test]
    public function everyone_with_export_permission_sees_templates_but_only_the_creator_may_change_them(): void
    {
        $creator = User::factory()->create();
        $template = ProjectContentExportTemplate::factory()->create(['name' => 'Shared', 'created_by' => $creator->id]);

        $this->actingAsUserWith(self::EXPORT);
        $this->getJson(route('projects.content-export.templates.index'))
            ->assertOk()
            ->assertJsonPath('0.name', 'Shared')
            ->assertJsonPath('0.can_manage', false);
        $this->patchJson(route('projects.content-export.templates.update', $template), [
            'name' => 'Hijacked',
            'columns' => [['key' => 'system:project_name']],
        ])->assertForbidden();
        $this->deleteJson(route('projects.content-export.templates.destroy', $template))->assertForbidden();

        $this->actingAsUserWith(self::EXPORT, $creator);
        $this->patchJson(route('projects.content-export.templates.update', $template), [
            'name' => 'Shared',
            'columns' => [['key' => 'system:events']],
        ])->assertOk()->assertJsonPath('columns.0.key', 'system:events');
    }

    #[Test]
    public function admins_may_delete_foreign_templates(): void
    {
        $template = ProjectContentExportTemplate::factory()->create();

        $this->actingAsAdmin();
        $this->deleteJson(route('projects.content-export.templates.destroy', $template))->assertOk();

        $this->assertModelMissing($template);
    }
}
