<?php

use Artwork\Modules\Area\Models\Area;
use Artwork\Modules\Room\Models\Room;
use Artwork\Modules\Room\Models\RoomFile;
use Artwork\Modules\User\Models\User;
use Illuminate\Http\UploadedFile;

beforeEach(function () {

    $this->auth_user = User::factory()->create();

    $this->assigned_user = User::factory()->create();

    $this->area = Area::factory()->create();

    $this->room = Room::factory()->create(['area_id' => $this->area->id]);

    $this->room_file = RoomFile::factory()->create(['room_id' => $this->room->id]);
    $this->auth_user->givePermissionTo(\Artwork\Modules\Permission\Enums\PermissionEnum::ROOM_UPDATE->value);
    $this->actingAs($this->auth_user);
});

test('authorized users can upload files to a room', function () {

    $response = $this->post("/rooms/{$this->room->id}/files", [
        'file' => UploadedFile::fake()->create('document.pdf', 100),
    ]);

    $this->assertDatabaseHas('room_files', [
        'name' => 'document.pdf',
        'room_id' => $this->room->id
    ]);
});

test('non authenticated users cannot upload files to a room', function () {
    $response = $this->post("/rooms/{$this->room->id}/files", [
        'file' => UploadedFile::fake()->create('document.pdf', 100),
    ]);

    $response->assertRedirect();

});

test('users can delete files from a room', function () {

    $this->delete("/room_files/{$this->room_file->id}");

    $this->assertSoftDeleted('room_files', [
        "id" => $this->room_file->id,
    ]);
});

test('users can force delete files from a room', function () {

    $this->delete("/room_files/{$this->room_file->id}");
    $this->delete("/room_files/{$this->room_file->id}/force_delete");

    $this->assertDatabaseMissing('room_files', [
        "id" => $this->room_file->id
    ]);
});

test('non authenticated users cannot delete files from a room', function () {

    $this->actingAs($this->auth_user);

    $this->delete("/room_files/{$this->room_file->id}")->assertRedirect();

});
