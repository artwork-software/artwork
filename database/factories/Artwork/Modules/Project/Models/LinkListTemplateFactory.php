<?php

namespace Database\Factories\Artwork\Modules\Project\Models;

use Artwork\Modules\Project\Models\LinkListTemplate;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LinkListTemplate>
 */
class LinkListTemplateFactory extends Factory
{
    protected $model = LinkListTemplate::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->words(2, true),
            'entries' => [],
        ];
    }
}
