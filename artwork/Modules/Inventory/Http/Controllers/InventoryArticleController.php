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
    ){
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        /** @var User $user */
        $user = $this->authManager->user();
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
        $articleIds = $request->get('article_ids', []);
        $startDate = $request->get('start_date');
        $endDate = $request->get('end_date');

        // Optimiere durch Eager Loading aller benötigten Daten
        $articles = InventoryArticle::whereIn('id', $articleIds)
            ->with([
                'internalIssues' => function ($query) use ($startDate, $endDate) {
                    $query->where(function ($q) use ($startDate, $endDate) {
                        $q->whereBetween('start_date', [$startDate, $endDate])
                          ->orWhereBetween('end_date', [$startDate, $endDate]);
                    });
                },
                'externalIssues' => function ($query) use ($startDate, $endDate) {
                    $query->where(function ($q) use ($startDate, $endDate) {
                        $q->whereBetween('issue_date', [$startDate, $endDate])
                          ->orWhereBetween('return_date', [$startDate, $endDate]);
                    });
                }
            ])
            ->get()
            ->keyBy('id');

        $results = [];
        foreach ($articleIds as $id) {
            if ($articles->has($id)) {
                $results[$id] = $articles[$id]->getAvailableStock($startDate, $endDate);
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
            ->load(['category', 'subCategory', 'detailedArticleQuantities', 'images']);

        return response()->json($articles);
    }
}
