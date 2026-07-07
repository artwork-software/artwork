<?php

namespace Database\Factories\Artwork\Modules\Budget\Models;

use Artwork\Modules\Budget\Models\Column;
use Artwork\Modules\Budget\Models\Table;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Column>
 */
class ColumnFactory extends Factory
{
    protected $model = Column::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'table_id' => Table::factory(),
            'name' => $this->faker->word(),
            'subName' => $this->faker->lexify('?'),
            'type' => 'empty',
            'position' => 0,
            'color' => 'whiteColumn',
            'is_locked' => false,
            'commented' => false,
            'relevant_for_project_groups' => false,
        ];
    }
}
