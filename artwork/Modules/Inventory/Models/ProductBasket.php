<?php

namespace Artwork\Modules\Inventory\Models;

use Artwork\Modules\User\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductBasket extends Model
{
    /** @use HasFactory<\Database\Factories\ProductBasketFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name'
    ];

    public function basketArticles(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(ProductBasketArticle::class, 'product_basket_id', 'id');
    }

    public function user(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'id', 'users');
    }
}
