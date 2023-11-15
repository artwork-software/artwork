<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\UserCommentedBudgetItemsSetting;
use Illuminate\Http\Request;

class UserCommentedBudgetItemsSettingController extends Controller
{
    /**
     * Store a newly created resource in storage.
     *
     * @param Request $request
     * @param User $user
     * @return void
     */
    public function store(Request $request, User $user): void
    {
        $validated = $request->validate(['exclude' => 'required|boolean']);
        $user->commented_budget_items_setting()->create([
            'exclude' => $validated['exclude']
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param Request $request
     * @param User $user
     * @param UserCommentedBudgetItemsSetting $commentedBudgetItemsSetting
     * @return void
     */
    public function update(
        Request $request,
        User $user,
        UserCommentedBudgetItemsSetting $commentedBudgetItemsSetting
    ): void {
        $validated = $request->validate(['exclude' => 'required|boolean']);
        $commentedBudgetItemsSetting->update(['exclude' => $validated['exclude']]);
    }
}
