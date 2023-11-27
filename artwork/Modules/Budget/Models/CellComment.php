<?php

namespace Artwork\Modules\Budget\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CellComment extends Model
{
    use HasFactory;

    protected $fillable = [
        'column_cell_id',
        'user_id',
        'description'
    ];

    //@todo switch with belongsToUserTrait once it's merged
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
