<?php

namespace Database\Factories\Artwork\Modules\Project\Models;

use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\Project\Models\ProjectFile;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<ProjectFile>
 */
class ProjectFileFactory extends Factory
{
    protected $model = ProjectFile::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            "name" => "Datei.pdf",
            "basename" => Str::random(20) . "Datei.pdf",
            "project_id" => Project::factory(),
        ];
    }
}
