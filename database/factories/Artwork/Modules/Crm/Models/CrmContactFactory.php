<?php

namespace Database\Factories\Artwork\Modules\Crm\Models;

use Artwork\Modules\Crm\Models\CrmContact;
use Artwork\Modules\Crm\Models\CrmContactType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CrmContact>
 */
class CrmContactFactory extends Factory
{
    protected $model = CrmContact::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'crm_contact_type_id' => CrmContactType::factory(),
            'display_name' => fake()->words(2, true),
        ];
    }
}
