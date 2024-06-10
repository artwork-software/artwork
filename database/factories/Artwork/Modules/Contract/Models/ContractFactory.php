<?php

namespace Database\Factories\Artwork\Modules\Contract\Models;

use Artwork\Modules\Contract\Models\Contract;
use Artwork\Modules\Project\Models\Project;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory
 */
class ContractFactory extends Factory
{
    protected $model = Contract::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->name,
            'basename' => '',
            'contract_partner' => 'Agentur XYZ',
            'amount' => 20000,
            'project_id' => Project::factory(),
            'description' => $this->faker->text(255),
            'ksk_liable' => false,
            'resident_abroad' => false,
        ];
    }
}
