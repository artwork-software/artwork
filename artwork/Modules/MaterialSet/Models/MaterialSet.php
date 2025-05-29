<?php

namespace Artwork\Modules\MaterialSet\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MaterialSet extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'description'];

    public function items(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(MaterialSetItem::class);
    }
}
