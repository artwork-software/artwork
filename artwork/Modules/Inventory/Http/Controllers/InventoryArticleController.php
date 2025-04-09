<?php

namespace Artwork\Modules\Inventory\Http\Controllers;

use App\Http\Controllers\Controller;
use Artwork\Modules\Inventory\Http\Requests\StoreInventoryArticleRequest;
use Artwork\Modules\Inventory\Http\Requests\UpdateInventoryArticleRequest;
use Artwork\Modules\Inventory\Models\InventoryArticle;
use Artwork\Modules\Inventory\Services\InventoryArticleService;
use Illuminate\Auth\AuthManager;
use Inertia\Inertia;
use JetBrains\PhpStorm\NoReturn;

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
}
