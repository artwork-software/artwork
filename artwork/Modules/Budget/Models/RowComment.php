<?php

namespace Artwork\Modules\Budget\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RowComment extends Model
{
    use HasFactory;

    protected $fillable = [
        'sub_position_row_id',
        'user_id',
        'description'
    ];

    //@todo use belongstouser trait
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
