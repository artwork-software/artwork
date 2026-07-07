<?php

namespace Tests\Concerns;

use Artwork\Modules\Event\Models\Event;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\Shift\Models\Shift;
use Artwork\Modules\Shift\Models\ShiftQualification;
use Artwork\Modules\User\Models\User;

trait BuildsDomainData
{
    protected function createProjectWithEvents(int $events = 1, array $overrides = []): Project
    {
        $project = Project::factory()->create($overrides);

        Event::factory()->count($events)->create(['project_id' => $project->id]);

        return $project->fresh('events');
    }

    protected function createShiftWithUsers(int $users = 2): Shift
    {
        $shift = Shift::factory()->create();

        User::factory()->count($users)->create()->each(
            fn (User $user) => $shift->users()->attach($user, [
                'shift_count' => 1,
            ])
        );

        return $shift->fresh('users');
    }

    protected function createUserWithQualifications(array $qualifications = []): User
    {
        $user = User::factory()->create();

        foreach ($qualifications as $qualificationName) {
            $qualification = ShiftQualification::query()->firstOrCreate(
                ['name' => $qualificationName],
                ['icon' => 'user-icon', 'available' => true]
            );
            $user->shiftQualifications()->attach($qualification);
        }

        return $user->fresh('shiftQualifications');
    }
}
