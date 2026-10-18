<?php

namespace Database\Factories\Artwork\Modules\Budget\Models;

use Artwork\Modules\Budget\Models\SageAssignedData;
use Artwork\Modules\Budget\Models\SageAssignedDataComment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SageAssignedDataComment>
 */
class SageAssignedDataCommentFactory extends Factory
{
    protected $model = SageAssignedDataComment::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'sage_assigned_data_id' => SageAssignedData::factory(),
            'comment' => fake()->sentence(),
        ];
    }
}
