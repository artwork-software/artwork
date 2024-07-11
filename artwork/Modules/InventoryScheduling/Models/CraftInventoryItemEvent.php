<?php

namespace Artwork\Modules\InventoryScheduling\Models;

use Artwork\Core\Database\Models\Model;
use Artwork\Modules\Event\Models\Event;
use Artwork\Modules\InventoryManagement\Models\CraftInventoryItem;
use Artwork\Modules\User\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $craft_inventory_item_id
 * @property int $event_id
 * @property int $quantity
 * @property string $comment
 * @property Carbon $start
 * @property Carbon $end
 * @property bool $is_all_day
 * @property int $user_id
 * @property CraftInventoryItem $item
 * @property Event $event
 * @property User $user
 */
class CraftInventoryItemEvent extends Model
{
    use HasFactory;

    protected $fillable = [
        'craft_inventory_item_id',
        'event_id',
        'quantity',
        'comment',
        'start',
        'end',
        'is_all_day',
        'user_id',
    ];

    protected $casts = [
        'start' => 'datetime',
        'end' => 'datetime',
        'is_all_day' => 'boolean',
    ];


    public function item(): BelongsTo
    {
        return $this->belongsTo(
            CraftInventoryItem::class,
            'craft_inventory_item_id',
            'id',
            'craft_inventory_items'
        );
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(
            Event::class,
            'event_id',
            'id',
            'events'
        );
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'user_id',
            'id',
            'users'
        );
    }
}
