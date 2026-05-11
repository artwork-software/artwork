<?php

namespace Database\Factories\Artwork\Modules\Checklist\Models;

use Artwork\Modules\Checklist\Models\ChecklistTemplate;
use Artwork\Modules\User\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ChecklistTemplate>
 */
class ChecklistTemplateFactory extends Factory
{
    protected $model = ChecklistTemplate::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->name(),
            'user_id' => User::factory(),
        ];
    }
}
