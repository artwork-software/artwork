<?php

namespace Tests\Feature\Visibility;

use Artwork\Modules\Department\Models\Department;
use Artwork\Modules\Event\Models\Event;
use Artwork\Modules\Inventory\Models\InventoryArticle;
use Artwork\Modules\Permission\Enums\PermissionEnum;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\Room\Models\Room;
use Artwork\Modules\Shift\Models\Shift;
use Artwork\Modules\Shift\Models\ShiftQualification;
use Artwork\Modules\User\Models\User;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * core-visibility-scopes: je Entitaet ein Scope, der abbildet, was die Oberflaeche zeigt
 * (Konzept B1 §2). Die Regeln sind extrahiert, nicht erfunden — das Inventar mit allen
 * Fundstellen steht in logs/IMPLEMENTATION_LOG_core-visibility-scopes.md §1.
 */
final class VisibleForScopesTest extends TestCase
{
    // ---------------------------------------------------------------- Project

    #[Test]
    public function a_user_without_rights_and_without_team_sees_no_project(): void
    {
        $user = User::factory()->create();
        Project::factory()->create();

        $this->assertSame(0, Project::query()->visibleFor($user)->count());
    }

    #[Test]
    public function membership_in_the_project_team_makes_a_project_visible(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create();
        $other = Project::factory()->create();
        $project->users()->attach($user);

        $visible = Project::query()->visibleFor($user)->pluck('id');

        $this->assertTrue($visible->contains($project->id));
        $this->assertFalse($visible->contains($other->id));
    }

    #[Test]
    public function membership_in_an_assigned_department_makes_a_project_visible(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create();
        $department = Department::factory()->create();
        $department->users()->attach($user);
        $project->departments()->attach($department);

        $this->assertTrue(Project::query()->visibleFor($user)->pluck('id')->contains($project->id));
    }

    #[Test]
    public function the_global_view_permission_shows_every_project(): void
    {
        $user = $this->userWith(PermissionEnum::PROJECT_VIEW->value);
        Project::factory()->count(3)->create();

        $this->assertSame(Project::query()->count(), Project::query()->visibleFor($user)->count());
    }

    /**
     * Gate::before gibt Artwork-Admins jede Abfrage frei — die Projektuebersicht rechnet damit
     * (canAny), der Scope muss sich deshalb genauso verhalten (HANDOVER_core §6).
     */
    #[Test]
    public function an_artwork_admin_sees_every_project(): void
    {
        $admin = $this->adminUser();
        Project::factory()->count(2)->create();

        $this->assertSame(Project::query()->count(), Project::query()->visibleFor($admin)->count());
    }

    #[Test]
    public function without_a_user_no_project_is_visible(): void
    {
        Project::factory()->create();

        $this->assertSame(0, Project::query()->visibleFor(null)->count());
    }

    // ------------------------------------------------------------------ Shift

    #[Test]
    public function without_the_shift_plan_permission_only_own_shifts_are_visible(): void
    {
        $user = User::factory()->create();
        $ownShift = Shift::factory()->create();
        $foreignShift = Shift::factory()->create();
        $ownShift->users()->attach($user->id, [
            'shift_qualification_id' => ShiftQualification::factory()->create()->id,
        ]);

        $visible = Shift::query()->visibleFor($user)->pluck('id');

        $this->assertTrue($visible->contains($ownShift->id));
        $this->assertFalse($visible->contains($foreignShift->id));
    }

    #[Test]
    public function the_shift_plan_permission_shows_every_shift(): void
    {
        $user = $this->userWith(PermissionEnum::VIEW_SHIFT_PLAN->value);
        Shift::factory()->count(2)->create();

        $this->assertSame(Shift::query()->count(), Shift::query()->visibleFor($user)->count());
    }

    #[Test]
    public function an_artwork_admin_sees_every_shift(): void
    {
        $admin = $this->adminUser();
        Shift::factory()->count(2)->create();

        $this->assertSame(Shift::query()->count(), Shift::query()->visibleFor($admin)->count());
    }

    #[Test]
    public function without_a_user_no_shift_is_visible(): void
    {
        Shift::factory()->create();

        $this->assertSame(0, Shift::query()->visibleFor(null)->count());
    }

    // ----------------------------------------------- Event, Room, Inventory

    /**
     * Kalender und Inventarliste haengen an keinem Recht — jede angemeldete Person sieht alles.
     * Der Test haelt genau das fest: aendert sich die Oberflaeche, muss er mitwandern.
     */
    #[Test]
    public function every_signed_in_user_sees_all_events_rooms_and_articles(): void
    {
        $user = User::factory()->create();
        Event::factory()->count(2)->create();
        Room::factory()->count(2)->create();
        InventoryArticle::factory()->count(2)->create();

        $this->assertSame(Event::query()->count(), Event::query()->visibleFor($user)->count());
        $this->assertSame(Room::query()->count(), Room::query()->visibleFor($user)->count());
        $this->assertSame(
            InventoryArticle::query()->count(),
            InventoryArticle::query()->visibleFor($user)->count()
        );
    }

    #[Test]
    public function without_a_user_nothing_is_visible(): void
    {
        Event::factory()->create();
        Room::factory()->create();
        InventoryArticle::factory()->create();

        $this->assertSame(0, Event::query()->visibleFor(null)->count());
        $this->assertSame(0, Room::query()->visibleFor(null)->count());
        $this->assertSame(0, InventoryArticle::query()->visibleFor(null)->count());
    }

    private function userWith(string $permission): User
    {
        $user = User::factory()->create();
        $this->actingAsUserWith($permission, $user);

        return $user->fresh();
    }
}
