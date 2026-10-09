<?php

namespace Database\Factories\Artwork\Modules\Inventory\Models;

use Artwork\Modules\Inventory\Models\InventoryArticleProperties;
use Artwork\Modules\Inventory\Models\InventoryPropertyValue;
use Artwork\Modules\User\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<InventoryPropertyValue>
 */
class InventoryPropertyValueFactory extends Factory
{
    protected $model = InventoryPropertyValue::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'inventory_article_property_id' => InventoryArticleProperties::factory(),
            'inventory_propertyable_type' => (new User())->getMorphClass(),
            'inventory_propertyable_id' => User::factory(),
            'value' => fake()->words(2, true),
        ];
    }
}
