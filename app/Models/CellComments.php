<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CellComments extends Model
{
    use HasFactory;

    protected $fillable = [
        'cell_id',
        'user',
        'description'
    ];

    public function cell(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(ColumnCell::class);
    }
}
