<?php

namespace App\Http\Controllers;

use Artwork\Modules\MoneySource\Models\MoneySourceCategory;
use Artwork\Modules\MoneySource\Models\MoneySourceCategoryMapping;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;

class MoneySourceCategoryController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate(
            [
                'name' => ['required', 'string', 'max:255', 'unique:money_source_categories,name'],
            ],
            [
                'name.required' => __('Please enter a name for the source category.'),
                'name.unique' => __('A source category with this name already exists.'),
            ]
        );

        MoneySourceCategory::create([
            'name' => $validated['name']
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
