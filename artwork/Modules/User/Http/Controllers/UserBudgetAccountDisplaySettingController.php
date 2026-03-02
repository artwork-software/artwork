<?php

namespace Artwork\Modules\User\Http\Controllers;

use App\Http\Controllers\Controller;
use Artwork\Modules\User\Models\User;
use Artwork\Modules\User\Models\UserBudgetAccountDisplaySetting;
use Illuminate\Http\Request;

class UserBudgetAccountDisplaySettingController extends Controller
{
    public function store(Request $request, User $user): void
    {
        $validated = $request->validate(['show_number' => 'required|boolean']);
        $user->budgetAccountDisplaySetting()->create([
            'show_number' => $validated['show_number']
        ]);
    }

    /* $user parameter is required otherwise the route model binding isn't working properly, suppress phpcs error
    phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundInExtendedClassBeforeLastUsed */
    public function update(
        Request $request,
        User $user,
        UserBudgetAccountDisplaySetting $budgetAccountDisplaySetting
    ): void {
        $validated = $request->validate(['show_number' => 'required|boolean']);
        $budgetAccountDisplaySetting->update(['show_number' => $validated['show_number']]);
    }
}
