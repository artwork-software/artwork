<?php

namespace Artwork\Modules\Project\Models;

use Artwork\Modules\Project\Models\Component;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DisclosureComponents extends Model
{
    use HasFactory;

    protected $fillable = [
        'disclosure_id',
        'component_id',
        'order',
        'scope'
    ];

    protected $casts = [
        'scope' => 'array'
    ];

    protected $with = ['component'];

    public function component(): BelongsTo
    {
        return $this->belongsTo(Component::class, 'component_id', 'id');
    }
}
