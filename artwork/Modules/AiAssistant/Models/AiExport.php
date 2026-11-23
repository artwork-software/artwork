<?php

namespace Artwork\Modules\AiAssistant\Models;

use Artwork\Modules\User\Models\User;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Ein PDF-Export aus der KI-Bar (core-ai-event-stats-exports).
 *
 * @property string $id
 * @property int $user_id
 * @property string $file gespeicherter Name (StoredFileName::forGenerated), zugleich Teil der URL
 * @property string $title
 * @property int $row_count
 * @property \Illuminate\Support\Carbon $expires_at
 */
class AiExport extends Model
{
    use HasUuids;

    public const DIRECTORY = 'pdf/ai';

    protected $fillable = [
        'user_id',
        'file',
        'title',
        'row_count',
        'expires_at',
    ];

    protected $casts = [
        'row_count' => 'integer',
        'expires_at' => 'datetime',
    ];

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function storagePath(): string
    {
        return self::DIRECTORY . '/' . $this->file;
    }
}
