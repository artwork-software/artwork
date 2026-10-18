<?php

namespace Artwork\Modules\User\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PdfExportUserFilter extends Model
{
    protected $fillable = [
        'user_id',
        'name',
        'filters',
    ];

    protected $casts = [
        'filters' => 'array',
    ];

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
