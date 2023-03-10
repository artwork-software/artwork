<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;

class MainPositionDetails extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function comments(): MorphMany
    {
        return $this->morphMany(SumComment::class, 'commentable');
    }

    public function mainPosition(): BelongsTo
    {
        return $this->belongsTo(MainPosition::class);
    }

    public function sumMoneySource(): MorphOne
    {
        return $this->morphOne(SumMoneySource::class, 'sourceable');
    }
}
