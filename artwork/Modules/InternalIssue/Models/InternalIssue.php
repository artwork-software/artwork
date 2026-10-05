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
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

/**
 * @property string $name
 * @property int $project_id
 * @property string $start_date
 * @property string $start_time
 * @property string $end_date
 * @property string $end_time
 * @property int $room_id
 * @property string $notes
 * @property bool $special_items_done
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
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

    /**
     * @return MorphToMany<InventoryArticle, $this>
     */
    public function articles(): \Illuminate\Database\Eloquent\Relations\MorphToMany
    {
        return $this->morphToMany(
            InventoryArticle::class,
            'issuable',
            'issuable_inventory_article'
        )
            // Artikel im Papierkorb bleiben Teil der Ausgabe (Entscheidung 05.10.2026); vorher
            // verschwanden sie aus der Anzeige und das nächste Speichern löste die Verknüpfung.
            ->withTrashed()
            ->withPivot('quantity')
            ->withTimestamps();
    }

    /**
     * @return MorphMany<SpecialItem, $this>
     */
    public function specialItems(): \Illuminate\Database\Eloquent\Relations\MorphMany
    {
        return $this->morphMany(SpecialItem::class, 'issuable');
    }

    /**
     * @return BelongsTo<Room, $this>
     */
    public function room(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Room::class, 'room_id', 'id', 'room');
    }

    /**
     * @return BelongsTo<Project, $this>
     */
    public function project(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Project::class, 'project_id', 'id', 'project');
    }

    /**
     * @return HasMany<InternalIssueFile, $this>
     */
    public function files(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(InternalIssueFile::class, 'internal_issue_id', 'id');
    }

    /**
     * @return BelongsToMany<User, $this>
     */
    public function responsibleUsers(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(User::class, 'internal_issue_responsible_users', 'internal_issue_id', 'user_id');
    }


    public function scopeOverlapping($query, ?string $from, ?string $to)
    {
        return $query
            // beide Grenzen vorhanden
            ->when($from && $to, function ($q) use ($from, $to): void {
                $q->where(function ($qq) use ($from, $to): void {
                    $qq->whereDate('start_date', '<=', $to)
                        ->where(function ($qqq) use ($from): void {
                            $qqq->whereNull('end_date')
                                ->orWhereDate('end_date', '>=', $from);
                        });
                });
            })
            // nur FROM: alles, das ab FROM noch läuft/endet
            ->when($from && !$to, function ($q) use ($from): void {
                $q->where(function ($qq) use ($from): void {
                    $qq->whereNull('end_date')
                        ->orWhereDate('end_date', '>=', $from);
                });
            })
            // nur TO: alles, das bis TO begonnen hat
            ->when(!$from && $to, function ($q) use ($to): void {
                $q->whereDate('start_date', '<=', $to);
            });
    }

    public function getStartDateTimeAttribute(): string
    {
        $date = $this->start_date ? Carbon::parse($this->start_date)->translatedFormat('d. F Y') : '';
        $time = $this->start_time ? Carbon::parse($this->start_time)->format('H:i') : '';
        return trim("$date $time");
    }

    public function getEndDateTimeAttribute(): string
    {
        if (!$this->end_date) {
            return '';
        }
        $date = Carbon::parse($this->end_date)->translatedFormat('d. F Y');
        $time = $this->end_time ? Carbon::parse($this->end_time)->format('H:i') : '';
        return trim("$date $time");
    }
}
