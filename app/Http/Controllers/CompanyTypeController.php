<?php

namespace App\Http\Controllers;

use Artwork\Modules\CompanyType\Models\CompanyType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;

class CompanyTypeController extends Controller
{
    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request): \Illuminate\Http\RedirectResponse
    {
        CompanyType::create([
            'name' => $request->name,
            'color' => $request->color
        ]);
        return Redirect::back();
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \Artwork\Modules\CompanyType\Models\CompanyType  $companyType
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(CompanyType $companyType): \Illuminate\Http\RedirectResponse
    {
        $companyType->delete();
        return Redirect::back();
    }

    public function forceDelete(int $id)
    {
        $companyType = CompanyType::onlyTrashed()->findOrFail($id);

        $companyType->forceDelete();

        return Redirect::route('projects.settings.trashed');
    }

    public function restore(int $id)
    {
        $companyType = CompanyType::onlyTrashed()->findOrFail($id);

        $companyType->restore();

        return Redirect::route('projects.settings.trashed');
    }

    public function update(Request $request, CompanyType $companyType): \Illuminate\Http\RedirectResponse
    {
        $companyType->update($request->only(['name', 'color']));

        return Redirect::back();
    }
}
