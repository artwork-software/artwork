<?php

namespace Artwork\Modules\Inventory\Http\Controllers;

use App\Http\Controllers\Controller;
use Artwork\Modules\ExternalIssue\Models\ExternalIssue;
use Artwork\Modules\InternalIssue\Models\InternalIssue;
use Artwork\Modules\Inventory\Http\Requests\StoreInventoryArticleRequest;
use Artwork\Modules\Inventory\Http\Requests\UpdateInventoryArticleRequest;
use Artwork\Modules\Inventory\Models\InventoryArticle;
use Artwork\Modules\Inventory\Services\InventoryArticleService;
use Artwork\Modules\Inventory\Services\InventoryPlanningService;
use Artwork\Modules\Inventory\Services\InventoryUserFilterService;
use Artwork\Modules\Inventory\Services\InventoryUserFilterShareService;
use Artwork\Modules\User\Models\User;
use Artwork\Modules\User\Services\UserService;
use Illuminate\Auth\AuthManager;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Illuminate\Http\Request;

class InventoryArticleController extends Controller
{

    public function __construct(
        private readonly InventoryArticleService $inventoryArticleService,
        private readonly AuthManager $authManager,
        protected InventoryPlanningService $inventoryPlanningService,
        private readonly InventoryUserFilterService $inventoryUserFilterService,
        private readonly InventoryUserFilterShareService $inventoryUserFilterShareService,
    ){
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {

        /** @var User $user */
        $user = $this->authManager->user();
        $this->inventoryUserFilterShareService->getFilterDataForUser($user);
        $data = $this->inventoryPlanningService->getAvailabilityData($user);

        if (request()?->has(['article_id', 'date'])) {
            $data['detailsForModal'] = $this->inventoryPlanningService->getDetailsForModal(
                request('article_id'),
                request('date')
            );
        }
        return Inertia::render('Inventory/InventoryArticlePlanning', $data);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    public function indexTrash()
    {
        return Inertia::render('Trash/InventoryArticles', [
            'trashedArticles' => $this->inventoryArticleService->getAllTrashed()
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreInventoryArticleRequest $request)
    {
        $this->inventoryArticleService->store($request);
    }
    /**
     * Display the specified resource.
     */
    public function show(InventoryArticle $inventoryArticle)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(InventoryArticle $inventoryArticle)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateInventoryArticleRequest $request, InventoryArticle $inventoryArticle)
    {
        $this->inventoryArticleService->update($inventoryArticle, $request);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(InventoryArticle $inventoryArticle)
    {
        $this->inventoryArticleService->delete($inventoryArticle);
    }

    public function forceDelete(int $inventoryArticle): void
    {
        /** @var InventoryArticle $article */
        $article = InventoryArticle::withTrashed()->findOrFail($inventoryArticle);
        $this->inventoryArticleService->forceDelete($article);
    }

    public function restore(int $inventoryArticle): void
    {
        /** @var InventoryArticle $article */
        $article = InventoryArticle::onlyTrashed()->findOrFail($inventoryArticle);
        $this->inventoryArticleService->restore($article);
    }

    public function availableStock(InventoryArticle $inventoryArticle, string $startDate, string $endDate)
    {
       // get all available stock for the given article
        // get article stock for the given date range in all issues and returns the available stock of the article
        $availableStock = $this->inventoryArticleService->getAvailableStock($inventoryArticle, $startDate, $endDate);
        return response()->json([
            'availableStock' => $availableStock
        ]);
    }

    public function availableStockBatch(Request $request)
    {

        $validated = $request->validate([
            'article_ids'   => ['required', 'array', 'min:1'],
            'article_ids.*' => ['integer', 'distinct', 'exists:inventory_articles,id'],
            'start_date'    => ['required', 'date'],
            'end_date'      => ['required', 'date', 'after_or_equal:start_date'],
            'type'          => ['nullable', 'in:intern,extern'],
            'issue_id'      => ['nullable', 'integer', 'min:1'],
        ]);

        $articleIds = array_map('intval', $validated['article_ids']);
        $startDate  = $validated['start_date'];
        $endDate    = $validated['end_date'];
        $type       = $validated['type'] ?? null;
        $issueId    = $validated['issue_id'] ?? null;


        $articles = InventoryArticle::whereIn('id', $articleIds)
            ->with([
                'statusValues',
                'detailedArticleQuantities.status',
                'internalIssues' => function ($q) use ($startDate, $endDate) {
                    $q->whereDate('start_date', '<=', $endDate)
                        ->where(function ($qq) use ($startDate) {
                            $qq->whereDate('end_date', '>=', $startDate)
                                ->orWhereNull('end_date');
                        });
                },
                'externalIssues' => function ($q) use ($startDate, $endDate) {
                    $q->whereDate('issue_date', '<=', $endDate)
                        ->where(function ($qq) use ($startDate) {
                            $qq->whereDate('return_date', '>=', $startDate)
                                ->orWhereNull('return_date');
                        });
                },
            ])
            ->get()
            ->keyBy('id');

        $results = [];
        foreach ($articleIds as $id) {
            if (isset($articles[$id])) {
                $results[$id] = $articles[$id]->getAvailableStock(
                    $startDate,
                    $endDate,
                    $issueId,
                    $type
                );
            }
        }

        return response()->json(['data' => $results]);
    }


    public function search(Request $request): \Illuminate\Http\JsonResponse
    {
        $search = $request->input('article_search');

        if (empty(trim($search))) {
            return response()->json([], 200); // Oder returniere eine sinnvolle Fehlermeldung
        }

        $articles = InventoryArticle::search($search)
            ->take(50)
            ->get()
            ->load(['category', 'subCategory', 'detailedArticleQuantities.status', 'images']);

        return response()->json($articles);
    }

    public function loadArticlesByFilter(Request $request) {
        $startDate = $request->get('start_date');
        $endDate = $request->get('end_date');
        /** @var User $user */
        $user = $this->authManager->user();
        $articlesByFilter = $this->inventoryUserFilterService->getFilteredArticles(
            $user,
            $startDate,
            $endDate
        );

        return response()->json([
            'articles' => $articlesByFilter->with(['category', 'subCategory', 'detailedArticleQuantities.status', 'images', 'statusValues', 'properties'])
                ->paginate(15)
        ]);
    }

    /**
     * Liefert alle Nutzungsdaten für das UsageModal eines Artikels zeitraum
     */
    public function usageData(Request $request)
    {
        $articleId = $request->get('article_id');
        $startDate = $request->get('start_date');
        $endDate = $request->get('end_date', $startDate);
        if (!$articleId || !$startDate || !$endDate) {
            return response()->json(['error' => 'article_id und date erforderlich'], 400);
        }
        $details = $this->inventoryPlanningService->getDetailsForModalRange($articleId, $startDate, $endDate);
        return response()->json(['data' => $details]);
    }
}
