<?php

namespace Artwork\Modules\Inventory\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Artwork\Modules\Inventory\DTOs\InventoryArticleDTO;
use Artwork\Modules\Inventory\DTOs\PaginatedInventoryArticleDTO;
use Artwork\Modules\Inventory\Models\InventoryArticle;
use Artwork\Modules\Inventory\Models\InventoryCategory;
use Artwork\Modules\Inventory\Models\InventorySubCategory;
use Artwork\Modules\Inventory\Services\InventoryArticleService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class InventoryArticleApiController extends Controller
{
    public function __construct(
        protected InventoryArticleService $articleService
    ) {}

    /**
     * Get a paginated list of inventory articles
     */
    public function index(Request $request): JsonResponse
    {
        $perPage = $request->input('per_page', 15);
        if ($perPage > 100) {
            $perPage = 100;
        }
        $search = $request->input('search', '');
        $categoryId = $request->input('category_id');
        $subCategoryId = $request->input('subcategory_id');

        $category = null;
        $subCategory = null;

        if ($categoryId) {
            $category = InventoryCategory::find($categoryId);
        }

        if ($subCategoryId) {
            $subCategory = InventorySubCategory::find($subCategoryId);
        }

        $articles = $this->articleService->getArticleList($category, $subCategory, $search, $perPage);

        $paginatedDTO = PaginatedInventoryArticleDTO::fromPaginator($articles);

        return response()->json($paginatedDTO->toArray());
    }

    /**
     * Get a specific inventory article by ID
     */
    public function show(InventoryArticle $article): JsonResponse
    {
        // Load relationships
        $article->load(['properties', 'category', 'subCategory', 'images', 'detailedArticleQuantities.status', 'statusValues']);

        // Transform to DTO
        $articleDTO = InventoryArticleDTO::fromModel($article);

        return response()->json($articleDTO->toArray());
    }
}
