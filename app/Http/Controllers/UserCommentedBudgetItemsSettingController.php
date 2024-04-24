<?php

namespace App\Http\Controllers;

use Artwork\Modules\User\Models\User;
use Artwork\Modules\UserCommentedBudgetItemsSetting\Models\UserCommentedBudgetItemsSetting;
use Illuminate\Http\Request;

class UserCommentedBudgetItemsSettingController extends Controller
{
    public function store(Request $request, User $user): void
    {
        $validated = $request->validate(['exclude' => 'required|boolean']);
        $user->commentedBudgetItemsSetting()->create([
            'exclude' => $validated['exclude']
        ]);
    }

    /* $user parameter is required otherwise the route model binding isn't working properly, suppress phpcs error
    phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundInExtendedClassBeforeLastUsed */
    public function update(
        Request $request,
        User $user,
        UserCommentedBudgetItemsSetting $commentedBudgetItemsSetting
    ): void {
        $validated = $request->validate(['exclude' => 'required|boolean']);
        $commentedBudgetItemsSetting->update(['exclude' => $validated['exclude']]);
    }
}
