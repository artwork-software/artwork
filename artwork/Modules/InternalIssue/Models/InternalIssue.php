<?php

namespace Artwork\Modules\InternalIssue\Models;

use Artwork\Core\Casts\TranslatedDateTimeCast;
use Artwork\Modules\Inventory\Models\InventoryArticle;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\Room\Models\Room;
use Artwork\Modules\User\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property string name
 * @property int project_id
 * @property string start_date
 * @property string start_time
 * @property string end_date
 * @property string end_time
 * @property int room_id
 * @property string notes
 * @property bool special_items_done
 * @property \Illuminate\Support\Carbon|null created_at
 * @property \Illuminate\Support\Carbon|null updated_at
 */
class InternalIssue extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 'project_id', 'start_date', 'start_time', 'end_date', 'end_time',
        'room_id', 'notes', 'special_items_done'
    ];

    protected $casts = [
        'responsible_user_ids' => 'array',
        'special_items_done' => 'boolean',
        'start_date' => 'date:Y-m-d',
        'end_date' => 'date:Y-m-d',
    ];

    protected $appends = [
        'start_date_time',
        'end_date_time'
    ];

    public function articles(): \Illuminate\Database\Eloquent\Relations\MorphToMany
    {
        return $this->morphToMany(
            InventoryArticle::class,
            'issuable',
            'issuable_inventory_article'
        )->withPivot('quantity')->withTimestamps();
    }

    public function specialItems(): \Illuminate\Database\Eloquent\Relations\MorphMany
    {
        return $this->morphMany(SpecialItem::class, 'issuable');
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

    public function responsibleUsers(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(User::class, 'internal_issue_responsible_users', 'internal_issue_id', 'user_id');
    }

    public function getStartDateTimeAttribute(): string
    {
        return Carbon::parse($this->start_date)->translatedFormat('d. F Y') . ' ' . Carbon::parse($this->start_time)->format('H:i');
    }

    public function getEndDateTimeAttribute(): string
    {
        return Carbon::parse($this->end_date)->translatedFormat('d. F Y') . ' ' . Carbon::parse($this->end_time)->format('H:i');
    }
}
