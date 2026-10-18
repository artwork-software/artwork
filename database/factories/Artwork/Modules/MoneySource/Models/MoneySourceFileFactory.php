<?php

namespace Database\Factories\Artwork\Modules\MoneySource\Models;

use Artwork\Modules\MoneySource\Models\MoneySourceFile;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MoneySourceFile>
 */
class MoneySourceFileFactory extends Factory
{
    protected $model = MoneySourceFile::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->words(2, true),
            'basename' => fake()->unique()->bothify('Basename-####-????'),
            'money_source_id' => fake()->numberBetween(1, 1000),
        ];
    }
}
