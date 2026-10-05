<?php

namespace Database\Factories\Artwork\Modules\BusinessIntelligence\Models;

use Artwork\Modules\BusinessIntelligence\Models\BiTimeEffort;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\User\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BiTimeEffort>
 */
class BiTimeEffortFactory extends Factory
{
    protected $model = BiTimeEffort::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'project_id' => Project::factory(),
            'label' => fake()->words(2, true),
            'effort_bucket' => \Artwork\Modules\BusinessIntelligence\Enums\BiEffortBucketEnum::cases()[0]->value,
            'user_id' => User::factory(),
        ];
    }
}
