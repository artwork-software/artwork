<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\EventType;
use App\Models\Genre;
use App\Models\Sector;
use App\Models\Task;
use App\Models\User;
use Artwork\Modules\Area\Models\Area;
use Artwork\Modules\Checklist\Models\Checklist;
use Artwork\Modules\Department\Models\Department;
use Artwork\Modules\Event\Models\Event;
use Artwork\Modules\Project\Models\Comment;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\Room\Models\RoomAttribute;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Auth;

class TestContentSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedModelsThatRequireNoRelationships();
        $this->seedRooms();

        // Users
        $users = User::factory()->count(10)->create();
        Auth::loginUsingId($users->last()->id);

        $this->seedProjects();

        // base models
        $departments = Department::all();
        $projects = Project::all();

        // comments
        foreach (array_fill(0, 10, null) as $count) {
            Comment::factory()->create([
                'project_id' => $projects->random()->id,
                'user_id' => $users->random()->id,
            ]);
        }

        // checklists with tasks
        foreach (array_fill(0, 6, null) as $count) {
            $checklist = Checklist::factory()->create([
                'project_id' => $projects->random()->id,
            ]);

            foreach (array_fill(0, random_int(1, 10), null) as $countSecond) {
                Comment::factory()->create([
                    'project_id' => $checklist->project_id,
                    'user_id' => $users->random()->id,
                ]);
            }

            foreach (array_fill(0, random_int(1, 10), null) as $countThird) {
                $task = Task::factory()->create([
                    'checklist_id' => $checklist->id,
                ]);
            }
        }

        // Events
        $this->seedEvents();

        // belongsToMany
        /** @var Department $department */
        foreach ($departments as $department) {
            $department->users()->sync($users->shuffle()->take(random_int(1, 3))->pluck('id'));
            $department->projects()->sync($projects->shuffle()->take(random_int(0, 2))->pluck('id'));
        }
    }

    private function seedModelsThatRequireNoRelationships(): void
    {
        Area::factory()->count(4)->create();
        Genre::factory()->count(8)->create();
        EventType::factory()->count(5)->create();
        Sector::factory()->count(2)->create();
        RoomAttribute::factory()->count(6)->create();
        Department::factory()->count(3)->create();

        foreach (['Festival', 'Public Performance', 'Workshop',] as $category) {
            Category::factory()->create(['name' => $category]);
        }
    }

    private function seedRooms(): void
    {
        $rooms = Room::factory()->count(6)->create();
        $categories = Category::all();
        $roomAttributes = RoomAttribute::all();

        $rooms->map(function (Room $room) use ($roomAttributes, $categories, $rooms): void {
            $room->adjoiningRooms()
                ->sync($rooms->shuffle()
                ->take(random_int(0, 2))
                ->pluck('id')
                ->filter(fn ($id) => $id !== $room->id));
            $room->categories()->sync($categories->shuffle()->take(random_int(1, 3))->pluck('id'));
            $room->attributes()->sync($roomAttributes->shuffle()->take(random_int(1, 3))->pluck('id'));
        });
    }

    private function seedProjects(): void
    {
        $sectors = Sector::all();
        $categories = Category::all();
        $genres = Genre::all();

        // Projects
        $project1 = Project::factory()->create([
            'sector_id' => $sectors->random()->id,
            'category_id' => $categories->random()->id,
            'genre_id' => $genres->random()->id,
        ]);

        $project2 = Project::factory()->create([
            'sector_id' => $sectors->random()->id,
            'category_id' => $categories->random()->id,
            'genre_id' => $genres->random()->id,
        ]);

        $project3 = Project::factory()->create([
            'sector_id' => $sectors->random()->id,
            'category_id' => $categories->random()->id,
            'genre_id' => $genres->random()->id,
        ]);
    }

    private function seedEvents(): void
    {
        $eventTypes = EventType::all();
        $rooms = Room::all();
        $projects = Project::all();
        $users = User::all();

        foreach (array_fill(0, 50, null) as $index => $null) {
            Event::factory()->create([
                'room_id' => $rooms->random()->id,
                'event_type_id' => $eventTypes->random()->id,
                'project_id' => $projects->random()->id,
                'user_id' => $users->random()->id,
            ]);
        }
    }
}
