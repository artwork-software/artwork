<?php

namespace Tests\Feature\Http\Controllers\Project;

use Artwork\Modules\Event\Models\Event;
use Artwork\Modules\Permission\Enums\PermissionEnum;
use Artwork\Modules\Project\Models\Component;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\User\Models\User;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

final class ProjectWriteEndpointsAuthTest extends FeatureTestCase
{
    #[Test]
    public function random_user_cannot_update_project_state(): void
    {
        $this->actingAs(User::factory()->create());
        $project = Project::factory()->create();

        $this->patch(route('update.project.state', ['project' => $project->id]), ['state' => 1])
            ->assertForbidden();
    }

    #[Test]
    public function admin_can_update_project_state(): void
    {
        $this->actingAsAdmin();
        $project = Project::factory()->create();

        $response = $this->patch(route('update.project.state', ['project' => $project->id]), ['state' => null]);

        $this->assertContains($response->status(), [200, 302]);
    }

    #[Test]
    public function project_manager_can_update_description(): void
    {
        // Projektleitung wird mit can_write=false angelegt — die Policy muss den
        // is_manager-Pivot trotzdem als Bearbeitungsrecht werten (InfoTab bietet es an).
        $user = User::factory()->create();
        $this->actingAs($user);
        $project = Project::factory()->create();
        $project->users()->attach($user->id, ['is_manager' => true, 'can_write' => false]);

        // Nur Autorisierung prüfbar: projects.description ist keine Spalte mehr,
        // der Endpunkt persistiert nichts (Legacy; Beschreibung läuft über Komponenten).
        $response = $this->patch(
            route('projects.update_description', ['project' => $project->id]),
            ['description' => 'Neue Beschreibung']
        );

        $this->assertContains($response->status(), [200, 302]);
    }

    #[Test]
    public function project_creator_can_update_description(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        $project = Project::factory()->create(['user_id' => $user->id]);

        $response = $this->patch(
            route('projects.update_description', ['project' => $project->id]),
            ['description' => 'Ersteller-Text']
        );

        $this->assertContains($response->status(), [200, 302]);
    }

    #[Test]
    public function viewer_can_update_shift_description_via_component_rule(): void
    {
        // Die Edit-UI der Schicht-Infos ist über die Komponenten-Einstellung gegated
        // (Default allSeeAndEdit) — ein Nutzer mit Projekt-Zutritt darf speichern.
        $this->actingAsUserWith(PermissionEnum::PROJECT_VIEW->value);
        $project = Project::factory()->create();
        Component::query()->updateOrCreate(
            ['type' => 'GeneralShiftInformationComponent'],
            ['name' => 'General Shift Information', 'data' => [], 'permission_type' => 'allSeeAndEdit']
        );

        $response = $this->patch(
            route('projects.update.shift_description', ['project' => $project->id]),
            ['shiftDescription' => 'Schicht-Info']
        );

        $this->assertContains($response->status(), [200, 302]);
        $this->assertSame('Schicht-Info', $project->fresh()->shift_description);
    }

    #[Test]
    public function user_without_project_access_cannot_update_shift_description(): void
    {
        $this->actingAs(User::factory()->create());
        $project = Project::factory()->create();

        $this->patch(
            route('projects.update.shift_description', ['project' => $project->id]),
            ['shiftDescription' => 'X']
        )->assertForbidden();
    }

    #[Test]
    public function random_user_cannot_create_sub_event(): void
    {
        $this->actingAs(User::factory()->create());
        $event = Event::factory()->create();

        $this->post(route('subEvent.add'), [
            'event_id' => $event->id,
            'eventName' => 'Sub',
            'start_time' => $event->start_time,
            'end_time' => $event->end_time,
            'event_type_id' => $event->event_type_id,
        ])->assertForbidden();
    }
}
