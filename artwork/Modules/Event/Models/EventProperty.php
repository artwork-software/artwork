<?php

namespace Artwork\Modules\Event\Models;

use Artwork\Core\Database\Models\Model;
use Artwork\Modules\Event\Models\Event;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class EventProperty extends Model
{
    use HasFactory;

    protected $fillable = [
        'icon',
        'name'
    ];

    public function events(): BelongsToMany
    {
        return $this->belongsToMany(Event::class);
    }
}
