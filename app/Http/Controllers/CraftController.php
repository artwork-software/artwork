<?php

namespace App\Http\Controllers;

use App\Models\Craft;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;

class CraftController extends Controller
{
    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request): \Illuminate\Http\RedirectResponse
    {
        $craft = Craft::create($request->only(['name', 'abbreviation']));
        if (!$request->assignable_by_all) {
            $craft->update(['assignable_by_all' => false]);
            $craft->users()->sync($request->users);
        }
        return Redirect::back();
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Craft  $craft
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Request $request, Craft $craft): \Illuminate\Http\RedirectResponse
    {
        $craft->update($request->only(['name', 'abbreviation']));
        if (!$request->assignable_by_all) {
            $craft->update(['assignable_by_all' => false]);
            $craft->users()->sync($request->users);
        } else {
            $craft->update(['assignable_by_all' => true]);
            $craft->users()->detach();
        }
        return Redirect::back();
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Craft  $craft
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(Craft $craft): \Illuminate\Http\RedirectResponse
    {
        $craft->users()->detach();
        $craft->delete();
        return Redirect::back()->with('success', 'Craft deleted');
    }
}
