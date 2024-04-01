<?php

namespace Database\Factories\Artwork\Modules\Budget\Models;

use Artwork\Modules\Budget\Models\Table;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\Checklist\Models\Checklist;
use Illuminate\Database\Eloquent\Factories\Factory;

class TableFactory extends Factory
{
    protected $model = Table::class;

    public function definition(): array
    {
        return [
            'project_id' => Project::factory(),
            'name' => $this->faker->word(),
            'is_template' => $this->faker->boolean(),
        ];
    }
}
