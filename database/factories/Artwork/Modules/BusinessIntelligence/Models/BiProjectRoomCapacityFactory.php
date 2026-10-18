<?php

namespace Database\Factories\Artwork\Modules\BusinessIntelligence\Models;

use Artwork\Modules\BusinessIntelligence\Models\BiProjectRoomCapacity;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\Room\Models\Room;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BiProjectRoomCapacity>
 */
class BiProjectRoomCapacityFactory extends Factory
{
    protected $model = BiProjectRoomCapacity::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'project_id' => Project::factory(),
            'room_id' => Room::factory(),
        ];
    }
}
