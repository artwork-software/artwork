<?php

namespace App\Http\Controllers;

use App\Models\CollectingSociety;
use App\Models\CompanyType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;

class CollectingSocietyController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function index(): \Illuminate\Database\Eloquent\Collection
    {
        return CollectingSociety::all();
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\CollectingSociety  $collectingSociety
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(CollectingSociety $collectingSociety): \Illuminate\Http\RedirectResponse
    {
        $collectingSociety->delete();
        return Redirect::back()->with('success', 'CollectingSociety deleted');
    }

    public function forceDelete(int $id)
    {
        $collectingSociety = CollectingSociety::onlyTrashed()->findOrFail($id);

        $collectingSociety->forceDelete();

        return Redirect::route('projects.settings.trashed')->with('success', 'CollectingSociety deleted');
    }

    public function restore(int $id)
    {
        $collectingSociety = CollectingSociety::onlyTrashed()->findOrFail($id);

        $collectingSociety->restore();

        return Redirect::route('projects.settings.trashed')->with('success', 'CollectingSociety restored');
    }
}
