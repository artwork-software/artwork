<?php

namespace Artwork\Modules\Trash\Http\Controllers;

use App\Http\Controllers\Controller;
use Artwork\Modules\Trash\Services\TrashCountService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TrashController extends Controller
{
    public function __construct(private readonly TrashCountService $trashCountService)
    {
    }

    /**
     * Zahl der Einträge je Tab für die Tab-Leiste des Papierkorbs.
     */
    public function counts(Request $request): JsonResponse
    {
        return response()->json(['counts' => $this->trashCountService->countsFor($request->user())]);
    }
}
