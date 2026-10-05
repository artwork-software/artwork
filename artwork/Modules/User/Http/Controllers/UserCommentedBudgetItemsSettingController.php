<?php

namespace Artwork\Modules\User\Http\Controllers;

use App\Http\Controllers\Controller;
use Artwork\Modules\User\Models\User;
use Artwork\Modules\User\Models\UserCommentedBudgetItemsSetting;
use Illuminate\Http\Request;

class UserCommentedBudgetItemsSettingController extends Controller
{
    public function store(Request $request, User $user): void
    {
        $this->authorize('updateOwnPreferences', $user);

        $validated = $request->validate(['exclude' => 'required|boolean']);
        $user->commentedBudgetItemsSetting()->create([
            'exclude' => $validated['exclude']
        ]);
    }

    public function update(
        Request $request,
        User $user,
        UserCommentedBudgetItemsSetting $commentedBudgetItemsSetting
    ): void {
        $this->authorize('updateOwnPreferences', $user);
        abort_unless((int) $commentedBudgetItemsSetting->user_id === (int) $user->id, 403);

        $validated = $request->validate(['exclude' => 'required|boolean']);
        $commentedBudgetItemsSetting->update(['exclude' => $validated['exclude']]);
    }
}
