<?php

namespace Database\Factories\Artwork\Modules\Room\Models;

use Artwork\Modules\Room\Models\Room;
use Artwork\Modules\Room\Models\RoomFile;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class RoomFileFactory extends Factory
{
    protected $model = RoomFile::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            "name" => "Datei.pdf",
            "basename" => Str::random(20) . "Datei.pdf",
            "room_id" => Room::factory()
        ];
    }
}
