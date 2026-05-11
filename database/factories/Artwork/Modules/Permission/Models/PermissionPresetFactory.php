<?php

namespace Database\Factories\Artwork\Modules\Permission\Models;

use Artwork\Modules\Permission\Models\PermissionPreset;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PermissionPreset>
 */
class PermissionPresetFactory extends Factory
{
    protected $model = PermissionPreset::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => 'Preset ' . $this->faker->unique()->word(),
            'permissions' => [],
        ];
    }
}
