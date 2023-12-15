<?php

namespace App\Http\Controllers;

use App\Models\MoneySourceCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;

class MoneySourceCategoryController extends Controller
{
    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request): \Illuminate\Http\RedirectResponse
    {
        MoneySourceCategory::create([
            'name' => $request->get('name')
        ]);
        return Redirect::back();
    }


    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\MoneySourceCategory  $moneySourceCategory
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(MoneySourceCategory $moneySourceCategory): \Illuminate\Http\RedirectResponse
    {
        $moneySourceCategory->delete();
        return Redirect::back();
    }
}
