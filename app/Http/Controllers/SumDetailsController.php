<?php

namespace App\Http\Controllers;

use App\Models\MoneySource;
use App\Support\Services\MoneySourceThresholdReminderService;
use Artwork\Modules\Budget\Models\SumMoneySource;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class SumDetailsController extends Controller
{
    public function store(
        Request $request,
        MoneySourceThresholdReminderService $moneySourceThresholdReminderService
    ): RedirectResponse {
        SumMoneySource::create([
            'linked_type' => $request->linked_type,
            'money_source_id' => $request->money_source_id,
            'sourceable_id' => $request->sourceable_id,
            'sourceable_type' => $request->sourceable_type
        ]);

        $moneySourceThresholdReminderService->handleThresholdReminders(MoneySource::find($request->money_source_id));

        return back();
    }

    public function update(
        SumMoneySource $sumMoneySource,
        Request $request,
        MoneySourceThresholdReminderService $moneySourceThresholdReminderService
    ): RedirectResponse {
        $sumMoneySource->update([
            'linked_type' => $request->linked_type,
            'money_source_id' => $request->money_source_id
        ]);

        if ($request->money_source_id) {
            $moneySourceThresholdReminderService
                ->handleThresholdReminders(MoneySource::find($request->money_source_id));
        }

        return back();
    }

    public function destroy(SumMoneySource $sumMoneySource): RedirectResponse
    {
        $sumMoneySource->delete();
        return back();
    }
}
