<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use function Clue\StreamFilter\fun;

class SubPositionRow extends Model
{
    use HasFactory;

    protected $fillable = [
        'commented',
        'position'
    ];

    public function subPosition(): BelongsTo
    {
        return $this->belongsTo(SubPosition::class);
    }

    public function columns(): BelongsToMany
    {
        return $this->belongsToMany(Column::class)
            ->with(['cell.comments' => function($query){
                return $query->orderBy('id', 'DESC');
            }])
            ->withTimestamps();
    }

}
