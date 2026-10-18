<?php

namespace Artwork\Modules\MaterialSet\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MaterialSet extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'description'];

    /**
     * @return HasMany<MaterialSetItem, $this>
     */
    public function items(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(MaterialSetItem::class);
    }
}
