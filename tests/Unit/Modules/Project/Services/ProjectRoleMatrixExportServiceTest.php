<?php

namespace Tests\Unit\Modules\Project\Services;

use Artwork\Modules\Event\Models\Event;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\Project\Models\ProjectRole;
use Artwork\Modules\Project\Services\ProjectRoleMatrixExportService;
use Artwork\Modules\User\Models\User;
use Carbon\Carbon;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class ProjectRoleMatrixExportServiceTest extends TestCase
{
    private ProjectRoleMatrixExportService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(ProjectRoleMatrixExportService::class);
    }

    private function createProjectWithEvent(string $start, string $end, array $attributes = []): Project
    {
        $project = Project::factory()->create($attributes);
        Event::factory()->create([
            'project_id' => $project->id,
            'start_time' => $start,
            'end_time' => $end,
        ]);

        return $project;
    }

    private function createUserWithDefaultRole(ProjectRole $role, array $attributes = []): User
    {
        $user = User::factory()->create($attributes);
        $user->defaultProjectRoles()->attach($role->id);

        return $user;
    }

    private function buildMatrix(string $start = '2026-06-01', string $end = '2026-06-30'): array
    {
        return $this->service->buildMatrix(
            Carbon::createFromFormat('Y-m-d', $start),
            Carbon::createFromFormat('Y-m-d', $end),
        );
    }

    #[Test]
    public function itListsPersonsWithDefaultRoleAndAssignmentInPeriod(): void
    {
        $role = ProjectRole::factory()->create(['name' => 'Regie']);
        $project = $this->createProjectWithEvent('2026-06-10 10:00:00', '2026-06-10 12:00:00');
        $user = $this->createUserWithDefaultRole($role, ['first_name' => 'Anna', 'last_name' => 'Muster']);
        $project->users()->attach($user->id, ['roles' => [$role->id]]);

        $matrix = $this->buildMatrix();

        self::assertCount(1, $matrix['projects']);
        self::assertSame($project->id, $matrix['projects'][0]['id']);
        self::assertCount(1, $matrix['rows']);
        self::assertSame('Anna Muster', $matrix['rows'][0]['name']);
        self::assertSame('Regie', $matrix['rows'][0]['roles'][$project->id]);
    }

    #[Test]
    public function itExcludesPersonsWithoutDefaultProjectRole(): void
    {
        $role = ProjectRole::factory()->create();
        $project = $this->createProjectWithEvent('2026-06-10 10:00:00', '2026-06-10 12:00:00');
        $userWithoutDefaultRole = User::factory()->create();
        $project->users()->attach($userWithoutDefaultRole->id, ['roles' => [$role->id]]);

        $matrix = $this->buildMatrix();

        self::assertCount(0, $matrix['rows']);
    }

    #[Test]
    public function itExcludesPersonsWithoutRoleAssignmentInPeriod(): void
    {
        $role = ProjectRole::factory()->create();
        $project = $this->createProjectWithEvent('2026-06-10 10:00:00', '2026-06-10 12:00:00');
        $user = $this->createUserWithDefaultRole($role);
        // im Team, aber ohne Projektrolle
        $project->users()->attach($user->id, ['roles' => []]);

        $matrix = $this->buildMatrix();

        self::assertCount(0, $matrix['rows']);
    }

    #[Test]
    public function itExcludesProjectsOutsideThePeriodAndGroups(): void
    {
        $role = ProjectRole::factory()->create();
        $projectBefore = $this->createProjectWithEvent('2026-05-01 10:00:00', '2026-05-01 12:00:00');
        $group = $this->createProjectWithEvent('2026-06-10 10:00:00', '2026-06-10 12:00:00', ['is_group' => true]);
        $user = $this->createUserWithDefaultRole($role);
        $projectBefore->users()->attach($user->id, ['roles' => [$role->id]]);
        $group->users()->attach($user->id, ['roles' => [$role->id]]);

        $matrix = $this->buildMatrix();

        self::assertCount(0, $matrix['projects']);
        self::assertCount(0, $matrix['rows']);
    }

    #[Test]
    public function itIncludesProjectsPartiallyOverlappingThePeriod(): void
    {
        $role = ProjectRole::factory()->create();
        // Termin ragt von Mai in den Juni hinein
        $project = $this->createProjectWithEvent('2026-05-30 10:00:00', '2026-06-02 12:00:00');
        $user = $this->createUserWithDefaultRole($role);
        $project->users()->attach($user->id, ['roles' => [$role->id]]);

        $matrix = $this->buildMatrix();

        self::assertCount(1, $matrix['projects']);
        self::assertCount(1, $matrix['rows']);
    }

    #[Test]
    public function itJoinsMultipleRoleNamesInOneCell(): void
    {
        $roleA = ProjectRole::factory()->create(['name' => 'Bühne']);
        $roleB = ProjectRole::factory()->create(['name' => 'Assistenz']);
        $project = $this->createProjectWithEvent('2026-06-10 10:00:00', '2026-06-10 12:00:00');
        $user = $this->createUserWithDefaultRole($roleA);
        $project->users()->attach($user->id, ['roles' => [$roleA->id, $roleB->id]]);

        $matrix = $this->buildMatrix();

        // Rollennamen alphabetisch sortiert
        self::assertSame('Assistenz, Bühne', $matrix['rows'][0]['roles'][$project->id]);
    }
}
