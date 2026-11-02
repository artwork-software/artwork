<?php

namespace Artwork\Modules\AppApi\Http\Controllers;

use App\Http\Controllers\Controller;
use Artwork\Modules\AppApi\Http\Requests\AppDateRangeRequest;
use Artwork\Modules\AppApi\Http\Requests\AppInventoryArticlesRequest;
use Artwork\Modules\AppApi\Services\AppInventoryService;
use Artwork\Modules\Inventory\Models\InventoryArticle;
use Illuminate\Http\JsonResponse;

/**
 * Read-only inventory for the app. Parity with the web: every user may look while the inventory
 * module is on (the route gate); tag restrictions limit editing only.
 */
class AppInventoryController extends Controller
{
    /** A phone list loads page by page; the web pages by 50. */
    private const PER_PAGE = 30;

    public function __construct(private readonly AppInventoryService $inventory)
    {
    }

    public function categories(): JsonResponse
    {
        return response()->json(['categories' => $this->inventory->categories()]);
    }

    public function index(AppInventoryArticlesRequest $request): JsonResponse
    {
        return response()->json($this->inventory->articlePage(
            $request->category(),
            $request->subCategory(),
            $request->search(),
            self::PER_PAGE,
        ));
    }

    public function show(InventoryArticle $article): JsonResponse
    {
        return response()->json(['article' => $this->inventory->article($article)]);
    }

    public function availability(AppDateRangeRequest $request, InventoryArticle $article): JsonResponse
    {
        $start = $request->startDate()->format('Y-m-d');
        $end = $request->endDate()->format('Y-m-d');

        return response()->json([
            'start' => $start,
            'end' => $end,
            ...$this->inventory->availability($article, $start, $end),
        ]);
    }
}
