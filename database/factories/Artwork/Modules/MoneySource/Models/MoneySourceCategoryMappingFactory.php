<?php

namespace Database\Factories\Artwork\Modules\MoneySource\Models;

use Artwork\Modules\MoneySource\Models\MoneySource;
use Artwork\Modules\MoneySource\Models\MoneySourceCategory;
use Artwork\Modules\MoneySource\Models\MoneySourceCategoryMapping;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MoneySourceCategoryMapping>
 */
class MoneySourceCategoryMappingFactory extends Factory
{
    protected $model = MoneySourceCategoryMapping::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'money_source_id' => MoneySource::factory(),
            'money_source_category_id' => MoneySourceCategory::factory(),
        ];
    }
}
