<?php

namespace App\Http\Controllers;

use App\Models\Copyright;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;

class CopyrightController extends Controller
{
    public function store(Request $request): RedirectResponse
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

    public function update(Request $request, Copyright $copyright): RedirectResponse
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
