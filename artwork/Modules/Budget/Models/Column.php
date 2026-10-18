<?php

namespace Artwork\Modules\Budget\Models;

use Artwork\Core\Database\Models\Model;
use Artwork\Modules\User\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property int $project_id
 * @property int $table_id
 * @property string $name
 * @property string $subName
 * @property string $type
 * @property int $position
 * @property int $linked_first_column
 * @property int $linked_second_column
 * @property bool $commented
 * @property bool $relevant_for_project_groups
 * @property Collection<ColumnCell> $cells
 * @property Collection<SubPositionSumDetail> $subPositionSumDetails
 * @property Collection<MainPositionDetails> $mainPositionSumDetails
 * @property Collection<BudgetSumDetails> $budgetSumDetails
 * @property User|null $lockedBy
 * @property string $color
 * @property bool $is_locked
 */
class Column extends Model
{
    use HasFactory;
    use BelongsToTable;
    use SoftDeletes;

    protected $fillable = [
        'table_id',
        'name',
        'subName',
        'type',
        'position',
        'linked_first_column',
        'linked_second_column',
        'color',
        'is_locked',
        'locked_by',
        'commented',
        'relevant_for_project_groups'
    ];

    protected $casts = [
        'is_locked' => 'boolean',
        'relevant_for_project_groups' => 'boolean',
    ];

    protected $with = [
        'lockedBy'
    ];

    /**
     * @return BelongsTo<Table, $this>
     */
    public function table(): BelongsTo
    {
        return $this->belongsTo(Table::class, 'table_id', 'id', 'tables');
    }

    /**
     * @return HasMany<ColumnCell, $this>
     */
    public function cells(): HasMany
    {
        return $this->hasMany(ColumnCell::class, 'column_id', 'id');
    }

    /**
     * @return HasMany<SubPositionSumDetail, $this>
     */
    public function subPositionSumDetails(): HasMany
    {
        return $this->hasMany(SubPositionSumDetail::class);
    }

    /**
     * @return HasMany<MainPositionDetails, $this>
     */
    public function mainPositionSumDetails(): HasMany
    {
        return $this->hasMany(MainPositionDetails::class);
    }

    /**
     * @return HasMany<BudgetSumDetails, $this>
     */
    public function budgetSumDetails(): HasMany
    {
        return $this->hasMany(BudgetSumDetails::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function lockedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'locked_by', 'id', 'locked_by');
    }
}
