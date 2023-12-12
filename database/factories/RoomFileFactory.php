<?php

namespace Database\Factories;

use App\Models\RoomFile;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<RoomFile>
 */
class RoomFileFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            "name" => "Datei.pdf",
            "basename" => Str::random(20) . "Datei.pdf",
            "room_id" => 1
        ];
    }
}
