<?php

declare(strict_types=1);

namespace Database\Seeders\Demo;

use Artwork\Modules\Area\Models\Area;
use Artwork\Modules\Category\Models\Category;
use Artwork\Modules\CostCenter\Models\CostCenter;
use Artwork\Modules\Department\Models\Department;
use Artwork\Modules\Event\Models\EventStatus;
use Artwork\Modules\EventType\Models\EventType;
use Artwork\Modules\Genre\Models\Genre;
use Artwork\Modules\Project\Models\ProjectState;
use Artwork\Modules\Room\Models\Room;
use Artwork\Modules\Room\Models\RoomAttribute;
use Artwork\Modules\Room\Models\RoomCategory;
use Artwork\Modules\Sector\Models\Sector;
use Artwork\Modules\User\Models\User;
use Database\Seeders\Demo\Support\DemoDataPools;
use Illuminate\Database\Seeder;

/**
 * Haus-Struktur des Artwork Testhauses: Areale, Räume, Termintypen,
 * Event-/Projektstatus, Projektattribute, Kostenstellen, Abteilungen.
 * Rein additiv: vorhandene Einträge (per Name) bleiben unangetastet.
 */
class DemoStructureSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedRooms();
        $this->seedEventTypes();
        $this->seedEventStatuses();
        $this->seedProjectStates();
        $this->seedProjectAttributes();
        $this->seedDepartments();
    }

    private function seedRooms(): void
    {
        $ownerId = User::query()->orderBy('id')->value('id');

        $areas = [];
        foreach (DemoDataPools::AREAS as $key => $data) {
            $areas[$key] = Area::firstOrCreate(['name' => $data['name']], ['color' => $data['color']]);
        }

        foreach (DemoDataPools::ROOM_CATEGORIES as $name) {
            RoomCategory::firstOrCreate(['name' => $name]);
        }
        foreach (DemoDataPools::ROOM_ATTRIBUTES as $name) {
            RoomAttribute::firstOrCreate(['name' => $name]);
        }
        $categories = RoomCategory::all()->keyBy('name');
        $attributes = RoomAttribute::all()->keyBy('name');

        $categoryByRole = [
            'main_stage' => 'Spielstätte', 'second_stage' => 'Spielstätte', 'foyer' => 'Spielstätte',
            'rehearsal' => 'Probenraum', 'workshop' => 'Werkstatt', 'outdoor' => 'Außenfläche',
        ];
        $attributesByRole = [
            'main_stage' => ['Bestuhlung variabel', 'Feste Tonregie', 'Verdunkelbar'],
            'second_stage' => ['Bestuhlung variabel', 'Verdunkelbar'],
            'foyer' => ['Tageslicht'],
            'rehearsal' => ['Tageslicht', 'Verdunkelbar'],
            'workshop' => ['Tageslicht'],
            'outdoor' => [],
        ];

        $created = 0;
        $position = (int) Room::query()->max('position');
        foreach (DemoDataPools::ROOMS as $data) {
            $room = Room::firstOrCreate(
                ['name' => $data['name']],
                [
                    'description' => $data['description'],
                    'color' => $data['color'],
                    'capacity' => $data['capacity'] ?? null,
                    'area_id' => $areas[$data['area']]->id,
                    'user_id' => $ownerId,
                    'order' => ++$position,
                    'position' => $position,
                    'temporary' => false,
                    'everyone_can_book' => $data['everyone_can_book'] ?? false,
                    'relevant_for_disposition' => in_array(
                        $data['role'],
                        ['main_stage', 'second_stage', 'foyer', 'outdoor'],
                        true
                    ),
                ]
            );
            if (!$room->wasRecentlyCreated) {
                // Kapazität nachziehen (für BI-Auslastung), ohne gepflegte Werte zu überschreiben
                if (($data['capacity'] ?? null) !== null && $room->capacity === null) {
                    $room->update(['capacity' => $data['capacity']]);
                }
                continue;
            }
            $created++;

            if (isset($categoryByRole[$data['role']], $categories[$categoryByRole[$data['role']]])) {
                $room->categories()->syncWithoutDetaching([$categories[$categoryByRole[$data['role']]]->id]);
            }
            $attributeIds = collect($attributesByRole[$data['role']] ?? [])
                ->map(static fn (string $name) => $attributes[$name]?->id)
                ->filter()
                ->all();
            if ($attributeIds !== []) {
                $room->attributes()->syncWithoutDetaching($attributeIds);
            }
        }

        $this->command?->info(sprintf('Räume: %d neu (in %d Arealen).', $created, count($areas)));
    }

    private function seedEventTypes(): void
    {
        $created = 0;
        foreach (DemoDataPools::EVENT_TYPES as $data) {
            $eventType = EventType::firstOrCreate(
                ['name' => $data['name']],
                [
                    'abbreviation' => $data['abbreviation'],
                    'hex_code' => $data['hex_code'],
                    'project_mandatory' => $data['project_mandatory'] ?? false,
                    'individual_name' => true,
                    'relevant_for_shift' => $data['relevant_for_shift'] ?? false,
                    'relevant_for_inventory' => $data['relevant_for_inventory'] ?? false,
                    'relevant_for_project_period' => $data['relevant_for_project_period'] ?? false,
                ]
            );
            if ($eventType->wasRecentlyCreated) {
                $created++;
            }
        }
        $this->command?->info(sprintf('Termintypen: %d neu.', $created));
    }

    private function seedEventStatuses(): void
    {
        foreach (DemoDataPools::EVENT_STATUSES as $data) {
            EventStatus::firstOrCreate(
                ['name' => $data['name']],
                ['color' => $data['color'], 'order' => $data['order'], 'default' => $data['default'] ?? false]
            );
        }
        $this->command?->info('Eventstatus angelegt.');
    }

    private function seedProjectStates(): void
    {
        foreach (DemoDataPools::PROJECT_STATES as $data) {
            ProjectState::withTrashed()->firstOrCreate(
                ['name' => $data['name']],
                ['color' => $data['color'], 'is_planning' => $data['is_planning']]
            );
        }
        $this->command?->info('Projektstatus angelegt.');
    }

    private function seedProjectAttributes(): void
    {
        foreach (DemoDataPools::CATEGORIES as $name) {
            Category::firstOrCreate(['name' => $name]);
        }
        foreach (DemoDataPools::GENRES as $name) {
            Genre::firstOrCreate(['name' => $name]);
        }
        foreach (DemoDataPools::SECTORS as $name) {
            Sector::firstOrCreate(['name' => $name]);
        }
        foreach (DemoDataPools::COST_CENTERS as $data) {
            CostCenter::firstOrCreate(['name' => $data['name']]);
        }
        $this->command?->info('Projektattribute (Kategorien/Genres/Bereiche/Kostenstellen) angelegt.');
    }

    private function seedDepartments(): void
    {
        foreach (DemoDataPools::DEPARTMENTS as $data) {
            Department::firstOrCreate(['name' => $data['name']], ['svg_name' => $data['svg_name']]);
        }
        $this->command?->info('Abteilungen angelegt.');
    }
}
