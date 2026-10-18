<?php

namespace Database\Factories\Artwork\Modules\InternalIssue\Models;

use Artwork\Modules\InternalIssue\Models\InternalIssue;
use Artwork\Modules\InternalIssue\Models\InternalIssueFile;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<InternalIssueFile>
 */
class InternalIssueFileFactory extends Factory
{
    protected $model = InternalIssueFile::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'internal_issue_id' => InternalIssue::factory(),
            'file_path' => fake()->words(2, true),
            'original_name' => fake()->words(2, true),
        ];
    }
}
