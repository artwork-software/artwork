<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class MainPositionDetails extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function comments(): MorphMany
    {
        return $this->morphMany(SumComment::class, 'commentable');
    }
}
