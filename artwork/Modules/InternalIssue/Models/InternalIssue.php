<?php

namespace Artwork\Modules\InternalIssue\Models;

use Artwork\Modules\Inventory\Models\InventoryArticle;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\Room\Models\Room;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InternalIssue extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 'project_id', 'start_date', 'start_time', 'end_date', 'end_time',
        'room_id', 'notes', 'responsible_user_ids', 'special_items_done'
    ];

    protected $casts = [
        'responsible_user_ids' => 'array',
        'special_items_done' => 'boolean',
        'start_date' => 'date',
        'end_date' => 'date',
        'start_time' => 'datetime:H:i',
        'end_time' => 'datetime:H:i'
    ];

    public function articles(): \Illuminate\Database\Eloquent\Relations\MorphToMany
    {
        return $this->morphToMany(
            InventoryArticle::class,
            'issuable',
            'issuable_inventory_article'
        )->withPivot('quantity')->withTimestamps();
    }

    public function specialItems(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(SpecialItem::class, 'internal_issue_id', 'id');
    }

    public function room(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Room::class, 'room_id', 'id', 'room');
    }

    public function project(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Project::class, 'project_id', 'id', 'project');
    }

    public function files(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(InternalIssueFile::class, 'internal_issue_id', 'id');
    }
}
