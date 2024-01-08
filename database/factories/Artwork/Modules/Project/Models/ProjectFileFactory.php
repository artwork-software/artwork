<?php

namespace Database\Factories\Artwork\Modules\Project\Models;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class ProjectFileFactory extends Factory
{
    protected $model = ProjectFileFactory::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            "name" => "Datei.pdf",
            "basename" => Str::random(20)."Datei.pdf",
            "project_id" => 1
        ];
    }
}
