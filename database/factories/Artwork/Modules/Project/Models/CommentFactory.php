<?php

namespace Database\Factories\Artwork\Modules\Project\Models;

use Artwork\Modules\Project\Models\Comment;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\User\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Comment>
 */
class CommentFactory extends Factory
{
    protected $model = Comment::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'text' =>  $this->faker->emoji . ' ' . $this->faker->text,
            'project_id' => Project::factory(),
            'user_id' => User::factory(),
        ];
    }
}
