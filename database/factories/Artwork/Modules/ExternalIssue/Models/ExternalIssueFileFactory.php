<?php

namespace Database\Factories\Artwork\Modules\ExternalIssue\Models;

use Artwork\Modules\ExternalIssue\Models\ExternalIssue;
use Artwork\Modules\ExternalIssue\Models\ExternalIssueFile;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ExternalIssueFile>
 */
class ExternalIssueFileFactory extends Factory
{
    protected $model = ExternalIssueFile::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'external_issue_id' => ExternalIssue::factory(),
            'file_path' => fake()->words(2, true),
            'original_name' => fake()->words(2, true),
        ];
    }
}
