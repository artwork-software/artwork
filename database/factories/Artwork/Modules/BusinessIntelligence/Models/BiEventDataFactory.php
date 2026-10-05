<?php

namespace Database\Factories\Artwork\Modules\BusinessIntelligence\Models;

use Artwork\Modules\BusinessIntelligence\Models\BiEventData;
use Artwork\Modules\Event\Models\Event;
use Artwork\Modules\Project\Models\Project;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BiEventData>
 */
class BiEventDataFactory extends Factory
{
    protected $model = BiEventData::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'project_id' => Project::factory(),
            'event_id' => Event::factory(),
        ];
    }
}
