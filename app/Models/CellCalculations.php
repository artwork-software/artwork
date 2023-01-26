<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CellCalculations extends Model
{
    use HasFactory;

    protected $fillable = [
        'cell_id',
        'name',
        'value',
        'description'
    ];

    public function cell()
    {
        return $this->belongsTo(ColumnCell::class);
    }
}
