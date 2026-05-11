<?php

namespace Tests\Feature\Http\Controllers;

use Artwork\Modules\Room\Models\Room;
use Artwork\Modules\Room\Models\RoomFile;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

final class RoomFileControllerTest extends FeatureTestCase
{
    #[Test]
    public function guest_cannot_store(): void
    {
        $room = Room::factory()->create();

        $this->post(route('room_files.store', $room), [])
            ->assertRedirect(route('login'));
    }

    #[Test]
    public function guest_cannot_download(): void
    {
        $room = Room::factory()->create();
        $file = RoomFile::query()->forceCreate([
            'room_id' => $room->id,
            'name' => 'doc.pdf',
            'basename' => 'abc.pdf',
        ]);

        $this->get('/room_files/' . $file->id)
            ->assertRedirect(route('login'));
    }

    #[Test]
    public function guest_cannot_destroy(): void
    {
        $room = Room::factory()->create();
        $file = RoomFile::query()->forceCreate([
            'room_id' => $room->id,
            'name' => 'doc.pdf',
            'basename' => 'abc.pdf',
        ]);

        $this->delete('/room_files/' . $file->id)
            ->assertRedirect(route('login'));
    }
}
