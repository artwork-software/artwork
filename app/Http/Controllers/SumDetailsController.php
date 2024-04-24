<?php

namespace App\Http\Controllers;

use Artwork\Modules\Budget\Models\SumMoneySource;
use Artwork\Modules\MoneySource\Models\MoneySource;
use Artwork\Modules\MoneySource\Services\MoneySourceCalculationService;
use Artwork\Modules\MoneySourceReminder\Services\MoneySourceThresholdReminderService;
use Artwork\Modules\Notification\Services\NotificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;

class SumDetailsController extends Controller
{
    public function __construct(
        private readonly MoneySourceThresholdReminderService $moneySourceThresholdReminderService,
        private readonly MoneySourceCalculationService $moneySourceCalculationService,
        private readonly NotificationService $notificationService
    ) {
    }

    public function store(Request $request): RedirectResponse
    {
        SumMoneySource::create([
            'linked_type' => $request->linked_type,
            'money_source_id' => $request->money_source_id,
            'sourceable_id' => $request->sourceable_id,
            'sourceable_type' => $request->sourceable_type
        ]);

        $this->moneySourceThresholdReminderService->handleThresholdReminders(
            MoneySource::find($request->money_source_id),
            $this->moneySourceCalculationService,
            $this->notificationService
        );

        return Redirect::back();
    }

    public function update(SumMoneySource $sumMoneySource, Request $request): RedirectResponse
    {
        $sumMoneySource->update([
            'linked_type' => $request->linked_type,
            'money_source_id' => $request->money_source_id
        ]);

        if ($request->money_source_id) {
            $this->moneySourceThresholdReminderService->handleThresholdReminders(
                MoneySource::find($request->money_source_id),
                $this->moneySourceCalculationService,
                $this->notificationService
            );
        }

        return Redirect::back();
    }

    public function destroy(SumMoneySource $sumMoneySource): RedirectResponse
    {
        $sumMoneySource->delete();

        return Redirect::back();
    }
}
