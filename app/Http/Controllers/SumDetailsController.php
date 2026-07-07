<?php

namespace App\Http\Controllers;

use Artwork\Modules\Budget\Models\BudgetSumDetails;
use Artwork\Modules\Budget\Models\MainPositionDetails;
use Artwork\Modules\Budget\Models\SubPositionSumDetail;
use Artwork\Modules\Budget\Models\SumMoneySource;
use Artwork\Modules\MoneySource\Models\MoneySource;
use Artwork\Modules\MoneySource\Services\MoneySourceCalculationService;
use Artwork\Modules\MoneySource\Services\MoneySourceThresholdReminderService;
use Artwork\Modules\Notification\Services\NotificationService;
use Illuminate\Http\JsonResponse;
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

    public function show(Request $request): JsonResponse
    {
        $type = $request->get('type'); // 'subPosition', 'mainPosition', or 'budget'
        $columnId = $request->get('column_id');
        $positionId = $request->get('position_id'); // subPosition_id, mainPosition_id, or budget type (COST/EARNING)

        $sumDetail = null;

        if ($type === 'subPosition' && $positionId && $columnId) {
            $sumDetail = SubPositionSumDetail::firstOrCreate([
                'sub_position_id' => $positionId,
                'column_id' => $columnId,
            ]);
            $sumDetail->load(['comments.user', 'sumMoneySource.moneySource']);
            $sumDetail = array_merge($sumDetail->toArray(), ['class' => SubPositionSumDetail::class]);
        }

        if ($type === 'mainPosition' && $positionId && $columnId) {
            $sumDetail = MainPositionDetails::firstOrCreate([
                'main_position_id' => $positionId,
                'column_id' => $columnId,
            ]);
            $sumDetail->load(['comments.user', 'sumMoneySource.moneySource']);
            $sumDetail = array_merge($sumDetail->toArray(), ['class' => MainPositionDetails::class]);
        }

        if ($type === 'budget' && $positionId && $columnId) {
            $sumDetail = BudgetSumDetails::firstOrCreate([
                'type' => $positionId, // COST or EARNING
                'column_id' => $columnId,
            ]);
            $sumDetail->load(['comments.user', 'sumMoneySource.moneySource']);
            $sumDetail = array_merge($sumDetail->toArray(), ['class' => BudgetSumDetails::class]);
        }

        return response()->json([
            'sumDetail' => $sumDetail
        ]);
    }

    public function store(Request $request): JsonResponse|RedirectResponse
    {
        $sumMoneySource = SumMoneySource::create([
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

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'sumMoneySource' => $sumMoneySource
            ]);
        }

        return Redirect::back();
    }

    public function update(SumMoneySource $sumMoneySource, Request $request): JsonResponse|RedirectResponse
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

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'sumMoneySource' => $sumMoneySource
            ]);
        }

        return Redirect::back();
    }

    public function destroy(SumMoneySource $sumMoneySource, Request $request): JsonResponse|RedirectResponse
    {
        $sumMoneySource->delete();

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true
            ]);
        }

        return Redirect::back();
    }
}
