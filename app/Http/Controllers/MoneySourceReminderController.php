<?php

namespace App\Http\Controllers;

use Artwork\Modules\MoneySource\Models\MoneySource;
use Artwork\Modules\MoneySourceReminder\Models\MoneySourceReminder;
use Illuminate\Http\Request;

class MoneySourceReminderController extends Controller
{
    public function store(MoneySource $moneySource, Request $request): void
    {
        foreach ($request->get('expirationReminders', []) as $expirationReminder) {
            $moneySource->reminder()->create([
                'type' => MoneySourceReminder::MONEY_SOURCE_REMINDER_TYPE_EXPIRATION,
                'value' => $expirationReminder['days']
            ]);
        }

        foreach ($request->get('thresholdReminders', []) as $thresholdReminder) {
            $moneySource->reminder()->create([
                'type' => MoneySourceReminder::MONEY_SOURCE_REMINDER_TYPE_THRESHOLD,
                'value' => $thresholdReminder['threshold']
            ]);
        }
    }
}
