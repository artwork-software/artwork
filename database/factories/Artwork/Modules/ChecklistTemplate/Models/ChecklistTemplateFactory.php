<?php

namespace Database\Factories\Artwork\Modules\ChecklistTemplate\Models;

use Artwork\Modules\ChecklistTemplate\Models\ChecklistTemplate;
use Artwork\Modules\User\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ChecklistTemplate>
 */
class ChecklistTemplateFactory extends Factory
{
    protected $model = ChecklistTemplate::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->name,
            'user_id' => User::factory()
        ];
    }
}
