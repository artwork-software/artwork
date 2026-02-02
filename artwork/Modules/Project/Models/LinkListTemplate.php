<?php

namespace Artwork\Modules\Project\Models;

use Artwork\Core\Database\Models\Model;
use Artwork\Modules\User\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property string $name
 * @property array $entries
 * @property int|null $created_by
 * @property string $created_at
 * @property string $updated_at
 * @property User|null $creator
 */
class LinkListTemplate extends Model
{
    use HasFactory;

    protected $table = 'link_list_templates';

    protected $fillable = [
        'name',
        'entries',
        'created_by',
    ];

    protected $casts = [
        'entries' => 'array',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
