<?php

namespace Artwork\Modules\AiAssistant\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Ein Objekt innerhalb eines Batches (core-undo; Konzept W3 §1).
 *
 * @property int $id
 * @property string $batch_id
 * @property string $model_type
 * @property int $model_id
 * @property string $action
 * @property array<string, mixed>|null $before_state
 */
class AiActionBatchItem extends Model
{
    public const ACTION_CREATE = 'create';

    public const ACTION_UPDATE = 'update';

    public const ACTION_DELETE = 'delete';

    protected $fillable = [
        'batch_id',
        'model_type',
        'model_id',
        'action',
        'before_state',
    ];

    protected $casts = [
        'before_state' => 'array',
    ];

    /** @return BelongsTo<AiActionBatch, $this> */
    public function batch(): BelongsTo
    {
        return $this->belongsTo(AiActionBatch::class, 'batch_id');
    }
}
