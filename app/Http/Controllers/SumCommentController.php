<?php

namespace App\Http\Controllers;

use Artwork\Modules\Budget\Http\Middleware\EnsureUserCanAccessProjectBudget;
use Artwork\Modules\Budget\Models\SumComment;
use Artwork\Modules\Permission\Enums\PermissionEnum;
use Artwork\Modules\Role\Enums\RoleEnum;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class SumCommentController extends Controller
{
    public function store(Request $request): JsonResponse|RedirectResponse
    {
        // commentable_type ist fillable: Allowlist, sonst ließe sich jede Model-Klasse eintragen.
        $validated = $request->validate([
            'comment' => ['required', 'string'],
            'commentable_id' => ['required', 'integer'],
            'commentable_type' => [
                'required',
                'string',
                Rule::in(EnsureUserCanAccessProjectBudget::SUM_MORPH_ALLOWLIST),
            ],
        ]);
        abort_unless($validated['commentable_type']::query()->whereKey($validated['commentable_id'])->exists(), 404);

        $comment = SumComment::create([
            'user_id' => Auth::id(),
            'comment' => $validated['comment'],
            'commentable_id' => $validated['commentable_id'],
            'commentable_type' => $validated['commentable_type'],
        ]);

        // Load user relation for the response
        $comment->load('user');

        if ($request->wantsJson()) {
            return response()->json([
                'comment' => $comment
            ]);
        }

        return back();
    }

    public function destroy(SumComment $comment, Request $request): JsonResponse|RedirectResponse
    {
        $user = $request->user();
        abort_unless(
            (int) $comment->user_id === (int) $user?->id
            || $user?->hasRole(RoleEnum::ARTWORK_ADMIN->value)
            || $user?->can(PermissionEnum::GLOBAL_PROJECT_BUDGET_ADMIN->value),
            403
        );

        $comment->delete();

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true
            ]);
        }

        return back();
    }
}
