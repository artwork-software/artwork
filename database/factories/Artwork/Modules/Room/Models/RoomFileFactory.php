<?php

namespace Database\Factories\Artwork\Modules\Room\Models;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\RoomFile>
 */
class RoomFileFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        return [
            "name" => "Datei.pdf",
            "basename" => Str::random(20)."Datei.pdf",
            "room_id" => 1
        ];
    }
}
