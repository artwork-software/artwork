<?php

namespace Database\Factories;

use App\Models\ProjectFile;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<ProjectFile>
 */
class ProjectFileFactory extends Factory
{
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
            "project_id" => 1
        ];
    }
}
