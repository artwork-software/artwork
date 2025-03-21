<?php

namespace Artwork\Modules\Inventory\Http\Controllers;

use App\Http\Controllers\Controller;
use Artwork\Modules\Inventory\Http\Requests\StoreInventoryArticleRequest;
use Artwork\Modules\Inventory\Http\Requests\UpdateInventoryArticleRequest;
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

        $article = InventoryArticle::create([
            'name' => $request->get('name'),
            'description' => $request->get('description'),
            'inventory_category_id' => $request->integer('inventory_category_id'),
            'quantity' => $request->integer('quantity'),
        ]);


        //dd($request->all());

        // store images
        $images = $request->file('images');
        if ( $images === null ){
            $images = [];
        }
        foreach ($images as $image) {
            $article->images()->create([
                'image' => $image->store('inventory_articles', 'public'),
                'is_main_image' => false,
                'order' => 0
            ]);
        }

        // find index of main image and set it to true
        $mainImageIndex = $request->integer('main_image_index');
        // check if index is valid
        if ( $mainImageIndex >= 0 && $mainImageIndex < count($images) ){
            $article->images[$mainImageIndex]->update(['is_main_image' => true]);
        }


        $freshArticle = $article->fresh();

        // properties
        $properties = $request->collect('properties');
        foreach ($properties as $property) {
            $freshArticle->properties()->attach((int)$property['id'], ['value' => (string)$property['value']]);
        }
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
