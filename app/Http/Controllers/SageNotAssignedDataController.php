<?php

namespace App\Http\Controllers;

use Artwork\Modules\Budget\Models\ColumnCell;
use Artwork\Modules\Budget\Models\SageNotAssignedData;
use Artwork\Modules\Budget\Services\SageNotAssignedDataService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;
use Inertia\Response;

class SageNotAssignedDataController extends Controller
{
    public function __construct(private readonly SageNotAssignedDataService $sageNotAssignedDataService)
    {
        $this->authorizeResource(SageNotAssignedData::class, 'sageNotAssignedData');
    }

    public function destroy(SageNotAssignedData $sageNotAssignedData): RedirectResponse
    {
        $this->sageNotAssignedDataService->delete($sageNotAssignedData);

        return Redirect::back();
    }

    /**
     * @throws AuthorizationException
     */
    public function getTrashed(Request $request): Response
    {
        $this->authorize('getTrashed', SageNotAssignedData::class);

        $search = trim((string) $request->input('search', ''));
        $perPage = (int) $request->input('entitiesPerPage', 25);

        $sageNotAssignedDataTrashed = SageNotAssignedData::onlyTrashed()
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($subQuery) use ($search) {
                    $subQuery->where('buchungstext', 'like', '%' . $search . '%')
                        ->orWhere('kreditor', 'like', '%' . $search . '%')
                        ->orWhere('belegnummer', 'like', '%' . $search . '%');
                });
            })
            ->orderByDesc('deleted_at')
            ->paginate($perPage)
            ->withQueryString();

        return Inertia::render(
            'Trash/SageNotAssignedData',
            [
                'sageNotAssignedDataTrashed' => $sageNotAssignedDataTrashed
            ]
        );
    }

    /**
     * @throws AuthorizationException
     */
    public function restore(SageNotAssignedData $sageNotAssignedData): RedirectResponse
    {
        $this->authorize('restore', $sageNotAssignedData);

        $this->sageNotAssignedDataService->restore($sageNotAssignedData);

        return Redirect::back();
    }

    /**
     * @throws AuthorizationException
     */
    public function forceDelete(SageNotAssignedData $sageNotAssignedData): RedirectResponse
    {
        $this->authorize('forceDelete', $sageNotAssignedData);

        $this->sageNotAssignedDataService->forceDelete($sageNotAssignedData);

        return Redirect::back();
    }

    public function forceDeleteAll(): RedirectResponse
    {
        SageNotAssignedData::onlyTrashed()->each(function ($item) {
            $this->sageNotAssignedDataService->forceDelete($item);
        });
        return Redirect::route('sageNotAssignedData.trashed');
    }

    public function moveSageData(
        SageNotAssignedData $sageNotAssignedData,
        ColumnCell $columnCell
    ): RedirectResponse {
        $this->sageNotAssignedDataService->moveSageData($sageNotAssignedData, $columnCell);
        return Redirect::back();
    }
}
