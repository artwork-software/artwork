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
        'scope',
        'upload_settings',
    ];

    protected $casts = [
        'scope' => 'array',
        'upload_settings' => 'array',
    ];

    protected $with = ['component'];

    /**
     * @return BelongsTo<Component, $this>
     */
    public function component(): BelongsTo
    {
        return $this->belongsTo(Component::class, 'component_id', 'id');
    }

    /**
     * Ordner (DisclosureComponent), in dem die Komponente liegt.
     *
     * @return BelongsTo<Component, $this>
     */
    public function disclosure(): BelongsTo
    {
        return $this->belongsTo(Component::class, 'disclosure_id', 'id');
    }
}
