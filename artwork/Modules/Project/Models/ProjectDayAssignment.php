<?php

namespace Artwork\Modules\Project\Models;

use Artwork\Core\Database\Models\Model;
use Artwork\Modules\Project\Enum\ProjectDayAssignmentType;
use Artwork\Modules\Shift\Contracts\Employable;
use Artwork\Modules\Shift\Models\Shift;
use Artwork\Modules\User\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Projektzuordnung im Dienstplan: ein Eintrag pro Person und Tag.
 * Zusammenhängende Anlage-Vorgänge (ganzer Projektzeitraum / mehrere Tage)
 * teilen sich eine group_id; is_full_period steuert das Mitverschieben bei
 * Terminverschiebungen.
 *
 * @property int $id
 * @property int $project_id
 * @property string $employable_type
 * @property int $employable_id
 * @property Carbon $date
 * @property string $type
 * @property string $group_id
 * @property bool $is_full_period
 * @property int|null $created_by
 * @property int|null $superseded_by_shift_id
 */
class ProjectDayAssignment extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'project_id',
        'employable_type',
        'employable_id',
        'date',
        'type',
        'group_id',
        'is_full_period',
        'created_by',
        'superseded_by_shift_id',
    ];

    protected $casts = [
        'date' => 'date:Y-m-d',
        'is_full_period' => 'boolean',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class, 'project_id', 'id', 'project');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by', 'id', 'createdBy');
    }

    public function supersededByShift(): BelongsTo
    {
        return $this->belongsTo(Shift::class, 'superseded_by_shift_id', 'id', 'supersededByShift');
    }

    public function getEmployableAttribute(): ?Employable
    {
        $employableType = $this->employable_type;
        $employableId = $this->employable_id;

        if (!$employableType || !$employableId) {
            return null;
        }

        return $employableType::find($employableId);
    }

    public function isBinding(): bool
    {
        return $this->type === ProjectDayAssignmentType::BINDING->value;
    }

    public function scopeForEmployable(Builder $builder, string $employableType, int $employableId): Builder
    {
        return $builder
            ->where('employable_type', $employableType)
            ->where('employable_id', $employableId);
    }

    public function scopeBetweenDates(Builder $builder, Carbon|string $start, Carbon|string $end): Builder
    {
        return $builder
            ->where('date', '>=', Carbon::parse($start)->toDateString())
            ->where('date', '<=', Carbon::parse($end)->toDateString());
    }

    public function scopeBinding(Builder $builder): Builder
    {
        return $builder->where('type', ProjectDayAssignmentType::BINDING->value);
    }

    public function scopeWish(Builder $builder): Builder
    {
        return $builder->where('type', ProjectDayAssignmentType::WISH->value);
    }
}
