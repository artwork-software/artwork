<?php

namespace Tests\Feature\Modules\Project;

use Artwork\Modules\Event\Models\Event;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\User\Models\User;
use Illuminate\Support\Facades\Gate;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

/**
 * Termin anlegen ≠ Projektrecht: Die Projektzuordnung im Termin-Dialog ist bewusst ohne
 * Projektprüfung möglich. Ein "Ersteller:in eines Termins im Projekt"-Zweig in
 * ProjectPolicy::update/delete gäbe damit jeder Person mit Terminrecht Schreib- und Löschrecht
 * an beliebigen Projekten. Der frühere Zweig las events.created_by (gibt es nicht) und war tot.
 */
final class ProjectPolicyEventCreatorTest extends FeatureTestCase
{
    #[Test]
    public function creating_an_event_in_a_project_grants_neither_write_nor_delete_rights(): void
    {
        $eventCreator = $this->actingAsUserWith([]);
        $project = Project::factory()->create();
        Event::factory()->create(['project_id' => $project->id, 'user_id' => $eventCreator->id]);

        $gate = Gate::forUser($eventCreator->fresh());
        $this->assertFalse($gate->allows('update', $project->fresh()));
        $this->assertFalse($gate->allows('delete', $project->fresh()));
    }

    #[Test]
    public function team_write_and_delete_rights_still_apply(): void
    {
        $project = Project::factory()->create();
        $writer = User::factory()->create();
        $deleter = User::factory()->create();
        $project->users()->attach($writer->id, ['can_write' => true]);
        $project->users()->attach($deleter->id, ['delete_permission' => true]);
        $this->actingAsUserWith([]);

        $this->assertTrue(Gate::forUser($writer)->allows('update', $project->fresh()));
        $this->assertTrue(Gate::forUser($deleter)->allows('delete', $project->fresh()));
    }
}
