<?php

namespace Artwork\Modules\Inventory\Http\Controllers;

use App\Http\Controllers\Controller;
use Artwork\Modules\Inventory\Http\Requests\StoreInventoryArticleRequest;
use Artwork\Modules\Inventory\Http\Requests\UpdateInventoryArticleRequest;
use Artwork\Modules\Inventory\Models\InventoryArticle;
use Artwork\Modules\Inventory\Services\InventoryArticleService;
use Illuminate\Auth\AuthManager;
use Inertia\Inertia;
use Illuminate\Http\Request;

class InventoryArticleController extends Controller
{

    public function __construct(
        private readonly InventoryArticleService $inventoryArticleService,
        private readonly AuthManager $authManager
    ){
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
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

        $results = [];

        foreach ($articleIds as $id) {
            $article = InventoryArticle::find($id);
            if ($article) {
                $results[$id] = $article->getAvailableStock($startDate, $endDate);
            }
        }

        return response()->json(['data' => $results]);
    }

    public function search(Request $request){
        $search = $request->get('search');
        // search for articles by name and return Articles with category and subcategory and quantity
        $articles = InventoryArticle::with(['category', 'subCategory'])
            ->where('name', 'like', "%$search%")
            ->get();

        return response()->json($articles);

    }
}
