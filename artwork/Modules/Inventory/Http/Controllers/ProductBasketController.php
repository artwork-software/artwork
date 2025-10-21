<?php

namespace Artwork\Modules\Inventory\Http\Controllers;

use App\Http\Controllers\Controller;
use Artwork\Modules\Inventory\Http\Requests\StoreProductBasketRequest;
use Artwork\Modules\Inventory\Http\Requests\UpdateProductBasketRequest;
use Artwork\Modules\Inventory\Models\ProductBasket;
use http\Env\Response;
use Illuminate\Auth\AuthManager;

class ProductBasketController extends Controller
{

    public function __construct(
        protected AuthManager $auth
    ) {
    }

    /**
     * Display a listing of the resource.
     */
    public function index(): \Illuminate\Http\JsonResponse
    {
        return response()->json([
            'baskets' => $this->auth->user()?->productBasket()->with(['basketArticles.article', 'basketArticles.article.images'])->get()
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): void
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreProductBasketRequest $request): void
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(ProductBasket $productBasket): void
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(ProductBasket $productBasket): void
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateProductBasketRequest $request, ProductBasket $productBasket): void
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(ProductBasket $productBasket): void
    {
        //
    }
}
