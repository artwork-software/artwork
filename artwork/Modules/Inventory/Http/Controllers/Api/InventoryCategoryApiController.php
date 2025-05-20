<?php

namespace Artwork\Modules\Inventory\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Artwork\Modules\Inventory\Services\InventoryCategoryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class InventoryCategoryApiController extends Controller
{
    public function __construct(
        protected InventoryCategoryService $categoryService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $perPage = $request->input('per_page', 15);
        $categories = $this->categoryService->paginateForApi($perPage);

        return response()->json($categories);
    }
}
