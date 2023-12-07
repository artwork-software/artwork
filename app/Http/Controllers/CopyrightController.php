<?php

namespace App\Http\Controllers;

use App\Models\Copyright;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;

class CopyrightController extends Controller
{
    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request): \Illuminate\Http\RedirectResponse
    {

        $collecting_society = (object) $request->collectingSociety;

        Copyright::create([
            'own_copyright' => $request->ownCopyright,
            'live_music' => $request->liveMusic,
            'collecting_society_id' => $collecting_society->id,
            'law_size' => $request->lawSize,
            'project_id' => $request->project_id,
        ]);
        return Redirect::back();
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Copyright  $copyright
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Request $request, Copyright $copyright): \Illuminate\Http\RedirectResponse
    {

        $collecting_society = (object) $request->collectingSociety;

        Copyright::where('id', $copyright->id)->update([
            'own_copyright' => $request->ownCopyright,
            'live_music' => $request->liveMusic,
            'collecting_society_id' => $collecting_society->id,
            'law_size' => $request->lawSize,
        ]);
        return Redirect::back();
    }
}
