<?php

namespace App\Http\Controllers;

use App\Http\Requests\FileUpload;
use App\Support\Services\NewHistoryService;
use Artwork\Modules\Project\Models\ProjectFile;
use Artwork\Modules\Room\Models\Room;
use Artwork\Modules\Room\Models\RoomFile;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;


class RoomFileController extends Controller
{

    protected ?NewHistoryService $historyController = null;

    public function __construct()
    {
        $this->historyController = new NewHistoryService('Room');
    }

    /**
     * @throws AuthorizationException
     */
    public function store(FileUpload $request, Room $room): \Illuminate\Http\RedirectResponse
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

        $this->historyController->createHistory($room->id, 'Dokument ' . $original_name . ' wurde hinzugefügt');

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
        $this->authorize('view projects');

        $this->historyController->createHistory($roomFile->room_id, 'Dokument ' . $roomFile->name . ' wurde heruntergeladen');

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
        //dd($roomFile);
        $this->authorize('view', $roomFile->room->area);

        $this->historyController->createHistory($roomFile->room_id, 'Dokument ' . $roomFile->name . ' wurde entfernt');

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
