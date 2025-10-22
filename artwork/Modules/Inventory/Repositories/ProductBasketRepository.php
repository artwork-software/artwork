<?php

namespace Artwork\Modules\Inventory\Repositories;

use Artwork\Modules\Inventory\Models\ProductBasket;
use Artwork\Modules\User\Models\User;

class ProductBasketRepository
{


    public function createBasisBasket(User $user): void
    {
        $user->productBasket()->create();
    }

    public function addArticleToBasket(ProductBasket $basket, int $articleId, int $quantity): void
    {
        // Prüfen, ob der Artikel bereits im Warenkorb existiert
        $existing = $basket->basketArticles()->where('article_id', $articleId)->first();

        if ($existing) {
            // Menge erhöhen
            $existing->update([
                'quantity' => $existing->quantity + $quantity,
            ]);
        } else {
            // Neuen Eintrag erstellen
            $basket->basketArticles()->create([
                'article_id' => $articleId,
                'quantity' => $quantity,
            ]);
        }
    }

}
