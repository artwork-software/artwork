<?php

namespace App\Http\Controllers;

use App\Models\ContractModule;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ContractModuleController extends Controller
{
    public function store(Request $request): RedirectResponse
    {

        if (!Storage::exists("contract_modules")) {
            Storage::makeDirectory("contract_modules");
        }

        $file = $request->file('module');

        if ($file) {
            $original_name = $file->getClientOriginalName();
            $basename = Str::random(20) . $original_name;

            Storage::putFileAs('contract_modules', $file, $basename);

            ContractModule::create([
                'name' => $original_name,
                'basename' => $basename,
            ]);

            return Redirect::back();
        } else {
            abort(400, "File missing");
        }
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
