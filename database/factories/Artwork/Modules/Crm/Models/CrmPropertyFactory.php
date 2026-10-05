<?php

namespace Database\Factories\Artwork\Modules\Crm\Models;

use Artwork\Modules\Crm\Models\CrmProperty;
use Artwork\Modules\Crm\Models\CrmPropertyGroup;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CrmProperty>
 */
class CrmPropertyFactory extends Factory
{
    protected $model = CrmProperty::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'crm_property_group_id' => CrmPropertyGroup::factory(),
            'name' => fake()->words(2, true),
            'type' => \Artwork\Modules\Crm\Enums\CrmPropertyTypeEnum::cases()[0]->value,
        ];
    }
}
