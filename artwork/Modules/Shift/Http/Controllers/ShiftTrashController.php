<?php

namespace Artwork\Modules\Shift\Http\Controllers;

use App\Http\Controllers\Controller;
use Artwork\Modules\Permission\Enums\PermissionEnum;
use Artwork\Modules\Shift\Models\Shift;
use Artwork\Modules\Shift\Services\ShiftTrashService;
use Artwork\Modules\User\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Response;
use RuntimeException;

/**
 * Papierkorb-Tab "Schichten": gelöschte eigenständige Schichten ansehen, wiederherstellen oder
 * endgültig löschen. Zugriff: Papierkorb-Recht + Dienstplanung (Gewerks-Scoping im Service).
 */
class ShiftTrashController extends Controller
{
    public function __construct(private readonly ShiftTrashService $shiftTrashService)
    {
    }

    public function index(Request $request): Response
    {
        $user = $this->plannerOrAbort($request);

        return inertia('Trash/Shifts', [
            'trashed_shifts' => $this->shiftTrashService->paginate(
                $user,
                trim((string) $request->input('search', '')),
                max(1, min(200, (int) $request->input('entitiesPerPage', 25)))
            ),
        ]);
    }

    public function restore(Request $request, int $shiftId): RedirectResponse
    {
        $user = $this->plannerOrAbort($request);

        try {
            $this->shiftTrashService->restore($user, $this->findTrashed($shiftId));
        } catch (RuntimeException $exception) {
            return redirect()->back()->with('error', $exception->getMessage());
        }

        return redirect()->back()->with('success', __('The shift has been restored.'));
    }

    public function forceDelete(Request $request, int $shiftId): RedirectResponse
    {
        $this->shiftTrashService->forceDelete($this->plannerOrAbort($request), $this->findTrashed($shiftId));

        return redirect()->back();
    }

    public function forceDeleteAll(Request $request): RedirectResponse
    {
        $this->shiftTrashService->forceDeleteAll($this->plannerOrAbort($request));

        return redirect()->back();
    }

    private function plannerOrAbort(Request $request): User
    {
        /** @var User|null $user */
        $user = $request->user();
        abort_unless($user?->can(PermissionEnum::SHIFT_PLANNER->value) === true, 403);

        return $user;
    }

    private function findTrashed(int $shiftId): Shift
    {
        return Shift::onlyTrashed()->whereNull('event_id')->findOrFail($shiftId);
    }
}
