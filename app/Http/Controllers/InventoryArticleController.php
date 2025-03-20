<?php

namespace App\Http\Controllers;

use Artwork\Modules\Inventory\Http\Requests\StoreInventoryArticleRequest;
use Artwork\Modules\Inventory\Http\Requests\UpdateInventoryArticleRequest;
use Artwork\Modules\Inventory\InventoryArticleRepository;
use Artwork\Modules\Inventory\Models\InventoryArticle;
use Artwork\Modules\Inventory\Services\InventoryArticleService;
use Illuminate\Auth\AuthManager;

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

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreInventoryArticleRequest $request)
    {
        dd($request->all());
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
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(InventoryArticle $inventoryArticle)
    {
        //
    }
}
