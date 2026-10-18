<?php

namespace Database\Factories\Artwork\Modules\BusinessIntelligence\Models;

use Artwork\Modules\BusinessIntelligence\Models\BiAudienceCategory;
use Artwork\Modules\BusinessIntelligence\Models\BiAudienceCategoryValue;
use Artwork\Modules\Project\Models\Project;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BiAudienceCategoryValue>
 */
class BiAudienceCategoryValueFactory extends Factory
{
    protected $model = BiAudienceCategoryValue::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'project_id' => Project::factory(),
            'bi_audience_category_id' => BiAudienceCategory::factory(),
        ];
    }
}
