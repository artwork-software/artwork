<?php

namespace Artwork\Modules\User\Http\Controllers;

use App\Http\Controllers\Controller;
use Artwork\Modules\User\Enums\UserFilterTypes;
use Artwork\Modules\User\Models\User;
use Artwork\Modules\User\Services\UserService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class UserShiftCalendarFilterController extends Controller
{
    public function update(Request $request, User $user): void
    {
        $this->authorize('updateOwnPreferences', $user);

        $user->shift_calendar_filter()->update($request->only([
            'event_types',
            'rooms',
        ]));
    }

    public function updateDates(Request $request, User $user, UserService $userService): void
    {
        $this->authorize('updateOwnPreferences', $user);

        $isDailyView = $request->get('isDailyView', false);

        $startDate = Carbon::parse($request->start_date)->format('Y-m-d');
        $endDate = Carbon::parse($request->end_date)->format('Y-m-d');

        // Geteilter Zeitraum aktiv: Navigation in einer Ansicht gilt für alle
        if ($user->share_calendar_date) {
            $userService->syncSharedCalendarFilterDates($user, $startDate, $endDate);

            return;
        }

        $filterType = $isDailyView
            ? UserFilterTypes::SHIFT_DAILY_FILTER->value
            : UserFilterTypes::SHIFT_FILTER->value;

        $user->userFilters()->updateOrCreate(
            ['filter_type' => $filterType],
            [
                'start_date' => $startDate,
                'end_date' => $endDate
            ]
        );
    }

    public function updateUserWorkerShiftPlanFilters(Request $request, User $user): void
    {
        $this->authorize('updateOwnPreferences', $user);

        $user->workerShiftPlanFilter()->update([
            'start_date' => $request->start_date ? Carbon::parse($request->start_date)->format('Y-m-d') : null,
            'end_date' => $request->end_date ? Carbon::parse($request->end_date)->format('Y-m-d') : null
        ]);
    }

    public function updateInventoryArticlePlanFilters(Request $request, User $user, UserService $userService): void
    {
        $this->authorize('updateOwnPreferences', $user);

        $startDate = Carbon::parse($request->get('start_date'))->format('Y-m-d');
        $endDate = Carbon::parse($request->get('end_date'))->format('Y-m-d');

        // Geteilter Zeitraum aktiv: Navigation in einer Ansicht gilt für alle
        if ($user->share_calendar_date) {
            $userService->syncSharedCalendarFilterDates($user, $startDate, $endDate);

            return;
        }

        $user->inventoryArticlePlanFilter()->updateOrCreate([], [
            'start_date' => $startDate,
            'end_date' => $endDate
        ]);
    }

    /**
     * Ref 1.18: persist the article planning view settings (only-planned toggle
     * and which categories/subcategories the user has expanded).
     */
    public function updateInventoryArticlePlanViewSettings(Request $request, User $user, UserService $userService): void
    {
        $this->authorize('updateOwnPreferences', $user);

        $validated = $request->validate([
            'only_planned' => ['boolean'],
            'open_categories' => ['array'],
            'open_categories.*' => ['string'],
            'open_subcategories' => ['array'],
            'open_subcategories.*' => ['string'],
            'share_calendar_date' => ['boolean'],
        ]);

        // Ansichtsübergreifendes Setting (users-Spalte); beim Einschalten aus der
        // Artikelplanung übernehmen alle Ansichten deren Zeitraum
        if ($request->has('share_calendar_date')) {
            $userService->updateShareCalendarDateSetting(
                $user,
                $request->boolean('share_calendar_date'),
                UserService::SHARE_DATE_SOURCE_INVENTORY_ARTICLE_PLAN
            );
        }

        $user->inventoryArticlePlanFilter()->updateOrCreate([], [
            'only_planned' => $validated['only_planned'] ?? false,
            'open_categories' => $validated['open_categories'] ?? [],
            'open_subcategories' => $validated['open_subcategories'] ?? [],
        ]);
    }

    public function updateListViewDates(Request $request, User $user, UserService $userService): void
    {
        $this->authorize('updateOwnPreferences', $user);

        $startDate = Carbon::parse($request->start_date)->format('Y-m-d');
        $endDate = Carbon::parse($request->end_date)->format('Y-m-d');

        // Geteilter Zeitraum aktiv: Navigation in einer Ansicht gilt für alle
        if ($user->share_calendar_date) {
            $userService->syncSharedCalendarFilterDates($user, $startDate, $endDate);

            return;
        }

        $user->userFilters()->updateOrCreate(
            ['filter_type' => UserFilterTypes::SHIFT_LIST_VIEW_FILTER->value],
            [
                'start_date' => $startDate,
                'end_date' => $endDate
            ]
        );
    }

    public function singleValueUpdate(Request $request, User $user): void
    {
        $this->authorize('updateOwnPreferences', $user);

        $user->shift_calendar_filter()->update([
            $request->key => $request->value
        ]);
    }

    public function reset(User $user): RedirectResponse
    {
        $this->authorize('updateOwnPreferences', $user);

        $user->shift_calendar_filter()->update([
            'event_types' => null,
            'rooms' => null,
        ]);

        return redirect()->back();
    }
}
