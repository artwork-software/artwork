<?php

namespace App\Http\Controllers;

use Artwork\Modules\MoneySourceCategory\Models\MoneySourceCategory;
use Artwork\Modules\MoneySourceCategoryMapping\Models\MoneySourceCategoryMapping;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;

class MoneySourceCategoryController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        MoneySourceCategory::create([
            'name' => $request->get('name')
        ]);
        return Redirect::back();
    }

    public function destroy(MoneySourceCategory $moneySourceCategory): RedirectResponse
    {
        foreach (
            MoneySourceCategoryMapping::query()
                ->where('money_source_category_id', '=', $moneySourceCategory->id)
                ->get() as $moneySourceCategoryMapping
        ) {
            $moneySourceCategoryMapping->delete();
        }

        $moneySourceCategory->delete();

        return Redirect::back();
    }
}
