<?php

namespace Artwork\Modules\Inventory\Http\Controllers;

use App\Http\Controllers\Controller;
use Artwork\Modules\Inventory\Http\Requests\StoreProductBasketArticleRequest;
use Artwork\Modules\Inventory\Http\Requests\UpdateProductBasketArticleRequest;
use Artwork\Modules\Inventory\Models\ProductBasket;
use Artwork\Modules\Inventory\Models\ProductBasketArticle;
use Artwork\Modules\Inventory\Repositories\ProductBasketArticleRepository;
use Artwork\Modules\Inventory\Services\ProductBasketService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProductBasketArticleController extends Controller
{

    public function __construct(
        protected ProductBasketService $productBasketService,
    ) {
    }

    /**
     * Display a listing of the resource.
     */
    public function index(): void
    {
        //
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
    public function store(StoreProductBasketArticleRequest $request): void
    {
        $this->productBasketService->addArticleToBasket(
            $request->input('article_id'),
            $request->input('quantity')
        );
    }

    public function updateQuantity(ProductBasketArticle $basketArticle, Request $request)
    {
        // Optional: Policy/Ownership prüfen
        // $this->authorize('update', $basketArticle);

        // 1) Validierung: entweder target ODER delta (string oder int)
        $data = $request->validate([
            'target' => ['sometimes', 'integer', 'min:0'],
            'delta'  => ['sometimes'], // int ODER string 'increase' | 'decrease'
            'steps'  => ['sometimes', 'integer', 'min:1'], // nur für string-delta relevant
        ]);

        // 2) Absolutmenge hat Vorrang (einfachster Pfad)
        if (array_key_exists('target', $data)) {
            $target = max(0, (int)$data['target']);
            $basketArticle->update(['quantity' => $target]);

            return response()->json([
                'basket_article_id' => $basketArticle->id,
                'quantity'          => $basketArticle->quantity,
            ]);
        }

        // 3) Delta normalisieren → int (bevorzugt numerisch, sonst strings + steps)
        if (!array_key_exists('delta', $data)) {
            return response()->json(['message' => 'Either target or delta is required.'], 422);
        }

        $delta = $data['delta'];

        if (is_numeric($delta)) {
            $delta = (int) $delta;
        } else {
            $steps = (int)($data['steps'] ?? 1);
            if ($delta === 'increase') {
                $delta = $steps;
            } elseif ($delta === 'decrease') {
                $delta = -$steps;
            } else {
                return response()->json(['message' => 'Invalid delta'], 422);
            }
        }

        // 4) Atomar aktualisieren (Race-Condition-sicher)
        DB::transaction(function () use (&$basketArticle, $delta) {
            /** @var ProductBasketArticle $fresh */
            $fresh = ProductBasketArticle::whereKey($basketArticle->id)
                ->lockForUpdate()
                ->first();

            $newQty = max(0, ($fresh->quantity ?? 0) + $delta);
            $fresh->update(['quantity' => $newQty]);

            // zurückreichen, damit wir unten den aktuellen Zustand senden
            $basketArticle = $fresh;
        });

        return response()->json([
            'basket_article_id' => $basketArticle->id,
            'quantity'          => $basketArticle->quantity,
        ]);
    }

    public function updateQuantitySingle(ProductBasketArticle $basketArticle, Request $request){
        $data = $request->validate([
            'quantity' => ['sometimes', 'integer', 'min:0'],
        ]);

        $basketArticle->update(['quantity' => $data['quantity']]);
    }

    /**
     * Display the specified resource.
     */
    public function show(ProductBasketArticle $productBasketArticle): void
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(ProductBasketArticle $productBasketArticle): void
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateProductBasketArticleRequest $request, ProductBasketArticle $productBasketArticle): void
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(ProductBasketArticle $basketArticle)
    {
        $basketArticle->delete();
        return response()->json(['deleted' => true, 'basket_article_id' => $basketArticle->id]);
    }

    public function removeArticles(ProductBasket $productBasket)
    {
        $productBasket->basketArticles()->delete();
    }
}
