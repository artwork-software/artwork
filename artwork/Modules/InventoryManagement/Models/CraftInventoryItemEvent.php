<?php

namespace Artwork\Modules\InventoryManagement\Models;

use Artwork\Modules\Event\Models\Event;
use Artwork\Modules\User\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CraftInventoryItemEvent extends Model
{
    use HasFactory;

    protected $fillable = [
        'craft_inventory_item_id',
        'event_id',
        'quantity',
        'comment',
        'date',
        'user_id',
    ];


    public function item(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(CraftInventoryItem::class, 'craft_inventory_item_id', 'id', 'craft_inventory_items');
    }

    public function event(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Event::class, 'event_id', 'id', 'events');
    }

    public function user(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'id', 'users');
    }
}
