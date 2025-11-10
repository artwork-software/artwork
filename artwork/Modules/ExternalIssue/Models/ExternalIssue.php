<?php

namespace Artwork\Modules\ExternalIssue\Models;

use Artwork\Modules\InternalIssue\Models\SpecialItem;
use Artwork\Modules\Inventory\Models\InventoryArticle;
use Artwork\Modules\User\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property string material_value
 * @property int issued_by_id
 * @property int received_by_id
 * @property string issue_date
 * @property string return_date
 * @property string return_remarks
 * @property string external_name
 * @property string external_address
 * @property string external_email
 * @property string external_phone
 * @property \Illuminate\Support\Carbon|null created_at
 * @property \Illuminate\Support\Carbon|null updated_at
 */
class ExternalIssue extends Model
{
    use HasFactory;

    protected $fillable = [
        'material_value', 'issued_by_id', 'received_by_id',
        'issue_date', 'return_date', 'return_remarks',
        'external_name', 'external_address', 'external_email', 'external_phone', 'special_items_done',
        'name'
    ];

    protected $casts = [
        'material_value' => 'decimal:2',
        'issued_by_id' => 'integer',
        'received_by_id' => 'integer',
        'special_items_done' => 'boolean',
    ];

    protected $appends = [
        'issue_date_formatted',
        'return_date_formatted'
    ];

    public function issuedBy(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class, 'issued_by_id', 'id', 'user');
    }

    public function receivedBy(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class, 'received_by_id', 'id', 'user');
    }

    public function files(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(ExternalIssueFile::class, 'external_issue_id', 'id');
    }

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

    public function getIssueDateFormattedAttribute(): string
    {
        return Carbon::parse($this->issue_date)->translatedFormat('d. F Y');
    }

    public function getReturnDateFormattedAttribute(): string
    {
        if ($this->return_date === null) {
            return '';
        }
        return Carbon::parse($this->return_date)->translatedFormat('d. F Y');
    }

    public function scopeOverlapping($query, ?string $from, ?string $to)
    {
        return $query
            // beide Grenzen gesetzt
            ->when($from && $to, function ($q) use ($from, $to) {
                $q->where(function ($qq) use ($from, $to) {
                    $qq->whereDate('issue_date', '<=', $to)
                        ->where(function ($qqq) use ($from) {
                            $qqq->whereNull('return_date')
                                ->orWhereDate('return_date', '>=', $from);
                        });
                });
            })
            // nur FROM: alles, was am/vor unendlicher Zukunft läuft & nicht vor FROM endet
            ->when($from && !$to, function ($q) use ($from) {
                $q->where(function ($qq) use ($from) {
                    $qq->whereNull('return_date')
                        ->orWhereDate('return_date', '>=', $from);
                });
            })
            // nur TO: alles, was bis TO begonnen hat
            ->when(!$from && $to, function ($q) use ($to) {
                $q->whereDate('issue_date', '<=', $to);
            });
    }
}
