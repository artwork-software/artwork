<?php

namespace App\Http\Controllers;

use App\Models\MoneySourceCategory;
use App\Models\MoneySourceCategoryMapping;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redirect;
use JetBrains\PhpStorm\NoReturn;

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
