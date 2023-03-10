<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class SumMoneySource extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function sourceable(): MorphTo
    {
        return $this->morphTo();
    }

    public function moneySource(): BelongsTo
    {
        return $this->belongsTo(MoneySource::class);
    }
}
