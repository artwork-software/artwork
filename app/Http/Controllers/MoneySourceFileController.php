<?php

namespace App\Http\Controllers;

use Artwork\Modules\Change\Services\ChangeService;
use Artwork\Modules\MoneySource\Models\MoneySource;
use Artwork\Modules\MoneySourceFile\Models\MoneySourceFile;
use Artwork\Modules\Project\Models\Comment;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

class MoneySourceFileController extends Controller
{
    public function __construct(private readonly ChangeService $changeService)
    {
    }

    /**
     * @throws AuthorizationException
     */
    public function store(
        Request $request,
        MoneySource $moneySource
    ): RedirectResponse {
        if (!Storage::exists("money_source_files")) {
            Storage::makeDirectory("money_source_files");
        }

        $file = $request->file('file');
        $original_name = $file->getClientOriginalName();
        $basename = Str::random(20) . $original_name;

        Storage::putFileAs('money_source_files', $file, $basename);

        $moneySourceFile = $moneySource->moneySourceFiles()->create([
            'name' => $original_name,
            'basename' => $basename,
        ]);

        if ($request->comment) {
            $comment = Comment::create([
                'text' => $request->comment,
                'user_id' => Auth::id(),
                'money_source_file_id' => $moneySourceFile->id
            ]);
            $moneySourceFile->comments()->save($comment);
        }

        $this->changeService->saveFromBuilder(
            $this->changeService
                ->createBuilder()
                ->setModelClass(MoneySource::class)
                ->setModelId($moneySource->id)
                ->setTranslationKey('Document uploaded')
                ->setTranslationKeyPlaceholderValues([$original_name])
        );

        return Redirect::back();
    }

    public function download(MoneySourceFile $moneySourceFile): StreamedResponse
    {
        return Storage::download('money_source_files/' . $moneySourceFile->basename, $moneySourceFile->name);
    }

    public function update(
        Request $request,
        MoneySourceFile $moneySourceFile
    ): RedirectResponse {
        if ($request->file('file')) {
            Storage::delete('money_source_files/' . $moneySourceFile->basename);
            $file = $request->file('file');
            $original_name = $file->getClientOriginalName();
            $basename = Str::random(20) . $original_name;

            $moneySourceFile->basename = $basename;
            $moneySourceFile->name = $original_name;

            Storage::putFileAs('money_source_files', $file, $basename);
        }

        if ($request->get('comment')) {
            $comment = Comment::create([
                'text' => $request->comment,
                'user_id' => Auth::id(),
                'money_source_file_id' => $moneySourceFile->id
            ]);
            $moneySourceFile->comments()->save($comment);
        }

        $moneySourceFile->save();

        return Redirect::back();
    }

    public function destroy(
        MoneySource $moneySource,
        MoneySourceFile $moneySourceFile
    ): RedirectResponse {
        $this->changeService->saveFromBuilder(
            $this->changeService
                ->createBuilder()
                ->setModelClass(MoneySource::class)
                ->setModelId($moneySource->id)
                ->setTranslationKey('Document deleted')
                ->setTranslationKeyPlaceholderValues([$moneySourceFile->name])
        );

        $moneySourceFile->delete();

        return Redirect::back();
    }
}
