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
    public function index()
    {
        return CollectingSociety::all();
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        CollectingSociety::create([
            'name' => $request->name
        ]);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\CollectingSociety  $collectingSociety
     * @return \Illuminate\Http\Response
     */
    public function show(CollectingSociety $collectingSociety)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\CollectingSociety  $collectingSociety
     * @return \Illuminate\Http\Response
     */
    public function edit(CollectingSociety $collectingSociety)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\CollectingSociety  $collectingSociety
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, CollectingSociety $collectingSociety)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\CollectingSociety  $collectingSociety
     * @return \Illuminate\Http\Response
     */
    public function destroy(CollectingSociety $collectingSociety)
    {
        $collectingSociety->delete();
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
