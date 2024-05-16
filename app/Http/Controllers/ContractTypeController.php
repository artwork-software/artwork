<?php

namespace App\Http\Controllers;

use Artwork\Modules\ContractType\Models\ContractType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;

class ContractTypeController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function index(): \Illuminate\Database\Eloquent\Collection
    {
        return ContractType::all();
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request): \Illuminate\Http\RedirectResponse
    {
        ContractType::create([
            'name' => $request->name,
            'color' => $request->color
        ]);
        return Redirect::back();
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \Artwork\Modules\ContractType\Models\ContractType  $contractType
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(ContractType $contractType): \Illuminate\Http\RedirectResponse
    {
        $contractType->delete();
        return Redirect::back();
    }

    public function forceDelete(int $id)
    {
        $contractType = ContractType::onlyTrashed()->findOrFail($id);

        $contractType->forceDelete();

        return Redirect::route('projects.settings.trashed');
    }

    public function restore(int $id)
    {
        $contractType = ContractType::onlyTrashed()->findOrFail($id);

        $contractType->restore();

        return Redirect::route('projects.settings.trashed');
    }

    public function update(Request $request, ContractType $contractType): \Illuminate\Http\RedirectResponse
    {
        $contractType->update($request->only(['name', 'color']));

        return Redirect::back();
    }
}
