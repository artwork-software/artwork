<?php

namespace App\Http\Controllers;

use App\Models\CollectingSociety;
use App\Models\Currency;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;

class CurrencyController extends Controller
{
    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request): \Illuminate\Http\RedirectResponse
    {
        Currency::create([
            'name' => $request->get('name')
        ]);
        return Redirect::back();
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Currency  $currency
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(Currency $currency): \Illuminate\Http\RedirectResponse
    {
        $currency->delete();
        return Redirect::back();
    }

    public function forceDelete(int $id)
    {
        $currency = Currency::onlyTrashed()->findOrFail($id);

        $currency->forceDelete();

        return Redirect::route('projects.settings.trashed');
    }

    public function restore(int $id)
    {
        $currency = Currency::onlyTrashed()->findOrFail($id);

        $currency->restore();

        return Redirect::route('projects.settings.trashed');
    }
}
