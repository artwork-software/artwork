<?php

namespace Database\Factories\Artwork\Modules\Checklist\Models;

use App\Models\Project;
use Artwork\Modules\Checklist\Models\Checklist;
use Illuminate\Database\Eloquent\Factories\Factory;

class ChecklistFactory extends Factory
{

    protected $model = Checklist::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->domainWord,
            'project_id' => Project::factory(),
        ];
    }
}
