<?php

namespace Tests\Feature\Projects;

use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\Shift\Models\Shift;
use Artwork\Modules\Shift\Models\ShiftQualification;
use Artwork\Modules\User\Models\User;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

/**
 * Projekt-Papierkorb: Mitglieder ohne Schichtliste, Relationen vorgeladen. Vorher lud die Seite je
 * Projekt alle Mitglieder samt aller ihrer Schichten (und je Schicht Termin, Raum, Gewerk).
 */
final class TrashedProjectsPayloadTest extends FeatureTestCase
{
    private function createTrashedProjectsWithMembers(int $projectCount): void
    {
        $qualification = ShiftQualification::factory()->create();
        foreach (range(1, $projectCount) as $ignored) {
            $project = Project::factory()->create();
            $member = User::factory()->create();
            $project->users()->attach($member->id);
            Shift::factory()->create()->users()->attach($member->id, ['shift_qualification_id' => $qualification->id]);
            $project->delete();
        }
    }

    private function countPageQueries(): int
    {
        DB::flushQueryLog();
        DB::enableQueryLog();
        $this->get(route('projects.trashed'))->assertOk();
        $count = count(DB::getQueryLog());
        DB::disableQueryLog();

        return $count;
    }

    #[Test]
    public function trashed_projects_list_members_without_shifts(): void
    {
        $this->actingAsAdmin();
        Project::onlyTrashed()->forceDelete();
        $this->createTrashedProjectsWithMembers(1);

        $this->get(route('projects.trashed'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Trash/Projects')
                ->where('trashed_projects.data.0.users.0.shifts', [])
                ->has('trashed_projects.data.0.users.0.departments'));
    }

    #[Test]
    public function member_queries_do_not_grow_with_the_number_of_projects(): void
    {
        $this->actingAsAdmin();
        Project::onlyTrashed()->forceDelete();
        $this->createTrashedProjectsWithMembers(2);
        $withTwo = $this->countPageQueries();

        $this->createTrashedProjectsWithMembers(4);
        $withSix = $this->countPageQueries();

        // Projektverlauf je Projekt bleibt (eine Abfrage je Projekt); Mitglieder/Abteilungen/Schichten nicht mehr
        self::assertLessThanOrEqual($withTwo + 4 * 2, $withSix);
    }
}
