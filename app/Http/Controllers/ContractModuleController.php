<?php

namespace App\Http\Controllers;

use Artwork\Core\FileHandling\Upload\ArtworkFileTypes;
use Artwork\Core\FileHandling\Upload\HandlesFileUpload;
use Artwork\Modules\Change\Services\ChangeService;
use Artwork\Modules\Contract\Models\ContractModule;
use Artwork\Modules\GeneralSettings\Services\GeneralSettingsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ContractModuleController extends Controller
{
    use HandlesFileUpload;

    public function __construct(
        private readonly ChangeService $changeService,
        private readonly GeneralSettingsService $generalSettingsService
    ) {
    }

    public function store(Request $request): RedirectResponse
    {

        if (!Storage::exists("contract_modules")) {
            Storage::makeDirectory("contract_modules");
        }

        $file = $request->file('file');
        if ($file) {
            $this->handleFile(ArtworkFileTypes::CONTRACT, $file);
            $original_name = $file->getClientOriginalName();
            $basename = Str::random(20) . $original_name;

            Storage::putFileAs('contract_modules', $file, $basename);

            ContractModule::create([
                'name' => $original_name,
                'basename' => $basename,
            ]);

            return Redirect::back();
        }

        abort(400, "File missing");
    }

    public function download(ContractModule $module): StreamedResponse
    {
        return Storage::download('contract_modules/' . $module->basename, $module->name);
    }

    public function destroy(ContractModule $module): RedirectResponse
    {
        $module->delete();
        return Redirect::route('contracts.index');
    }
}
