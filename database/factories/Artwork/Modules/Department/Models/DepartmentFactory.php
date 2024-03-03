<?php

namespace Database\Factories\Artwork\Modules\Department\Models;

use Artwork\Modules\Department\Models\Department;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Department>
 */
class DepartmentFactory extends Factory
{
    protected $model = Department::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => 'Team ' . $this->faker->domainName,
            'svg_name' => $this->faker->name
        ];
    }
}
