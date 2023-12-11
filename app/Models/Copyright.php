<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property bool $own_copyright
 * @property bool $live_music
 * @property int $collecting_society_id
 * @property string $law_size
 * @property int $project_id
 * @property string $created_at
 * @property string $updated_at
 */
class Copyright extends Model
{
    use HasFactory;

    protected $fillable = [
        'own_copyright',
        'live_music',
        'collecting_society_id',
        'law_size',
        'project_id'
    ];

    protected $casts = [
        'own_copyright' => 'boolean',
        'live_music' => 'boolean'
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class, 'project_id');
    }

    public function collecting_society(): BelongsTo
    {
        return $this->belongsTo(CollectingSociety::class, 'collecting_society_id');
    }
}
