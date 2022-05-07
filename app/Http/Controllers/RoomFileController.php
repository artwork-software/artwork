<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\ProjectFile;
use App\Models\Room;
use App\Models\RoomFile;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;


class RoomFileController extends Controller
{

    /**
     * @throws AuthorizationException
     */
    public function store(Request $request, Room $room): \Illuminate\Http\RedirectResponse
    {
        $this->authorize('view', $room->area);

        if (!Storage::exists("room_files")) {
            Storage::makeDirectory("room_files");
        }

        $file = $request->file('file');
        $original_name = $file->getClientOriginalName();
        $basename = Str::random(20).$original_name;

        Storage::putFileAs('room_files', $file, $basename);

        $room->room_files()->create([
            'name' => $original_name,
            'basename' => $basename,
        ]);

        return Redirect::back();
    }

    /**
     * Display the specified resource.
     *
     * @param ProjectFile $projectFile
     * @return StreamedResponse
     * @throws AuthorizationException
     */
    public function download(RoomFile $roomFile): StreamedResponse
    {
        $this->authorize('view', $roomFile->room->area);

        return Storage::download('room_files/'. $roomFile->basename, $roomFile->name);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param ProjectFile $projectFile
     * @return RedirectResponse
     * @throws AuthorizationException
     */
    public function destroy(RoomFile $roomFile)
    {
        $this->authorize('view', $roomFile->room->area);

        $roomFile->delete();

        return Redirect::back();
    }

    public function force_delete(int $id): \Illuminate\Http\RedirectResponse
    {

        $roomFile = RoomFile::onlyTrashed()->findOrFail($id);
        $this->authorize('view', $roomFile->room->area);

        Storage::delete('room_files/'. $roomFile->basename);

        $roomFile->forceDelete();
        return Redirect::back();
    }
}
