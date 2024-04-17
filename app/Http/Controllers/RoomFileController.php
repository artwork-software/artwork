<?php

namespace App\Http\Controllers;

use App\Http\Requests\FileUpload;
use Artwork\Modules\Change\Services\ChangeService;
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
    public function __construct(private readonly ChangeService $changeService)
    {
    }

    /**
     * @throws AuthorizationException
     */
    public function store(FileUpload $request, Room $room): RedirectResponse
    {
        $this->authorize('view', $room->area);

        if (!Storage::exists("room_files")) {
            Storage::makeDirectory("room_files");
        }

        $file = $request->file('file');
        $original_name = $file->getClientOriginalName();
        $basename = Str::random(20) . $original_name;

        Storage::putFileAs('room_files', $file, $basename);

        $room->room_files()->create([
            'name' => $original_name,
            'basename' => $basename,
        ]);

        $this->changeService->saveFromBuilder(
            $this->changeService
                ->createBuilder()
                ->setModelClass(Room::class)
                ->setModelId($room->id)
                ->setTranslationKey('Document was added')
                ->setTranslationKeyPlaceholderValues([$original_name])
        );

        return Redirect::back();
    }

    public function download(RoomFile $roomFile): StreamedResponse
    {
        $this->authorize('view projects');

        $this->changeService->saveFromBuilder(
            $this->changeService
                ->createBuilder()
                ->setModelClass(Room::class)
                ->setModelId($roomFile->room_id)
                ->setTranslationKey('Document has been downloaded')
                ->setTranslationKeyPlaceholderValues([$roomFile->name])
        );

        return Storage::download('room_files/' . $roomFile->basename, $roomFile->name);
    }

    public function destroy(RoomFile $roomFile): RedirectResponse
    {
        //dd($roomFile);
        $this->authorize('view', $roomFile->room->area);

        $this->changeService->saveFromBuilder(
            $this->changeService
                ->createBuilder()
                ->setModelClass(Room::class)
                ->setModelId($roomFile->room_id)
                ->setTranslationKey('Document has been removed')
                ->setTranslationKeyPlaceholderValues([$roomFile->name])
        );

        $roomFile->delete();

        return Redirect::back();
    }

    public function forceDelete(int $id): RedirectResponse
    {
        $roomFile = RoomFile::onlyTrashed()->findOrFail($id);
        $this->authorize('view', $roomFile->room->area);

        Storage::delete('room_files/' . $roomFile->basename);

        $roomFile->forceDelete();
        return Redirect::back();
    }
}
