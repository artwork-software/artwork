<?php

namespace Database\Factories\Artwork\Modules\ExternalUserManagement\Models;

use Artwork\Modules\ExternalUserManagement\Models\ExternalUserGroupMapping;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ExternalUserGroupMapping>
 */
class ExternalUserGroupMappingFactory extends Factory
{
    protected $model = ExternalUserGroupMapping::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'source_id' => fake()->numberBetween(1, 1000),
            'ad_group_dn' => fake()->words(2, true),
            'ad_group_name' => fake()->words(2, true),
        ];
    }
}
