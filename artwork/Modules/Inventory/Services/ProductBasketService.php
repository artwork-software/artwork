<?php

namespace Artwork\Modules\Inventory\Services;

use Artwork\Modules\Inventory\Models\ProductBasket;
use Artwork\Modules\Inventory\Repositories\ProductBasketRepository;
use Artwork\Modules\User\Models\User;
use Illuminate\Auth\AuthManager;

class ProductBasketService
{

    public function __construct(
        protected ProductBasketRepository $productBasketRepository,
        protected AuthManager $authManager
    ) {
    }


    public function createBasisBasket(User $user): void
    {
        $this->productBasketRepository->createBasisBasket($user);
    }

    public function addArticleToBasket(int $articleId, int $quantity): void
    {
        /** @var ProductBasket $basket */
        $basket = $this->getUserBasket();
        $this->productBasketRepository->addArticleToBasket($basket, $articleId, $quantity);
    }


    public function getUserBasket(): \Illuminate\Database\Eloquent\Model
    {
        /** @var User $user */
        $user = $this->authManager->user();
        return $user->productBasket()->with(['basketArticles' /*, 'basketArticles.article'*/])->firstOrCreate();
    }
}
