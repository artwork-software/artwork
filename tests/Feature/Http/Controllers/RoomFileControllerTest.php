<?php

namespace Tests\Feature\Http\Controllers;

use Artwork\Modules\Room\Models\Room;
use Artwork\Modules\Room\Models\RoomFile;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
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

    #[Test]
    public function stored_file_name_is_hashed_while_the_display_name_stays_raw(): void
    {
        $this->actingAsAdmin();
        $room = Room::factory()->create();
        $originalName = 'Raumplan °^„A“ 12:30.pdf';

        $this->post(route('room_files.store', $room), [
            'file' => UploadedFile::fake()->create($originalName, 10, 'application/pdf'),
        ]);

        $file = RoomFile::query()->firstOrFail();

        $this->assertSame($originalName, $file->name);
        $this->assertMatchesRegularExpression('/^[a-f0-9]{32}\.[a-z0-9]+$/', $file->basename);
        Storage::assertExists('room_files/' . $file->basename);
    }
}
