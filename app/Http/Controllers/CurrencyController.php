<?php

namespace App\Http\Controllers;

use Artwork\Modules\Contract\Models\Contract;
use Artwork\Modules\Currency\Models\Currency;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;

class CurrencyController extends Controller
{
    /**
     * Store a newly created resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request): \Illuminate\Http\RedirectResponse
    {
        Currency::create([
            'name' => $request->get('name'),
            'color' => $request->color
        ]);
        return Redirect::back();
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param \Artwork\Modules\Currency\Models\Currency $currency
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(Currency $currency): \Illuminate\Http\RedirectResponse
    {
        $currency->delete();
        return Redirect::back();
    }

    public function forceDelete(int $id): \Illuminate\Http\RedirectResponse
    {
        $currency = Currency::onlyTrashed()->findOrFail($id);

        if (Contract::withTrashed()->where('currency_id', $currency->id)->exists()) {
            return Redirect::back()->with(
                'error',
                __('This currency is still used by contracts and cannot be deleted permanently.')
            );
        }

        $currency->forceDelete();

        return Redirect::route('projects.settings.trashed');
    }

    public function restore(int $id): \Illuminate\Http\RedirectResponse
    {
        $currency = Currency::onlyTrashed()->findOrFail($id);

        $currency->restore();

        return Redirect::route('projects.settings.trashed');
    }

    public function update(Request $request, Currency $currency): \Illuminate\Http\RedirectResponse
    {
        $currency->update($request->only(['name', 'color']));

        return Redirect::back();
    }
}
