<?php

namespace App\Http\Controllers;

use App\Models\SumMoneySource;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class SumDetailsController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        SumMoneySource::create([
            'linked_type' => $request->linked_type,
            'money_source_id' => $request->money_source_id,
            'sourceable_id' => $request->sourceable_id,
            'sourceable_type' => $request->sourceable_type
        ]);

        return back();
    }

    public function update(SumMoneySource $sumMoneySource, Request $request): RedirectResponse
    {
        $sumMoneySource->update([
            'linked_type' => $request->linked_type,
            'money_source_id' => $request->money_source_id
        ]);

        return back();
    }

    public function destroy(SumMoneySource $sumMoneySource): RedirectResponse
    {
        $sumMoneySource->delete();
        return back();
    }
}
