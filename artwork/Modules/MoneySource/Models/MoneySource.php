<?php

namespace Artwork\Modules\MoneySource\Models;

use Antonrom\ModelChangesHistory\Traits\HasChangesHistory;
use Artwork\Core\Database\Models\Model;
use Artwork\Modules\Budget\Models\SumMoneySource;
use Artwork\Modules\MoneySourceCategory\Models\MoneySourceCategory;
use Artwork\Modules\MoneySourceCategoryMapping\Models\MoneySourceCategoryMapping;
use Artwork\Modules\MoneySourceFile\Models\MoneySourceFile;
use Artwork\Modules\MoneySourceReminder\Models\MoneySourceReminder;
use Artwork\Modules\MoneySourceTask\Models\MoneySourceTask;
use Artwork\Modules\MoneySourceUserPivot\Models\MoneySourceUserPivot;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\User\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Laravel\Scout\Searchable;

/**
 * @property int $id
 * @property int $creator_id
 * @property string $name
 * @property float $amount
 * @property string $source_name
 * @property string $start_date
 * @property string $end_date
 * @property Collection $users
 * @property int $group_id
 * @property string $description
 * @property int $is_group
 * @property string $created_at
 * @property string $updated_at
 */
class MoneySource extends Model
{
    use HasFactory;
    use Searchable;
    use HasChangesHistory;

    protected $fillable = [
        'name',
        'amount',
        'start_date',
        'end_date',
        'funding_start_date',
        'funding_end_date',
        'source_name',
        'description',
        'is_group',
        'users',
        'group_id',
        'sub_money_source_ids',
        'pinned_by_users'
    ];

    protected $casts = [
        'is_group' => 'boolean',
        'pinned_by_users' => 'array',
    ];

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'money_source_users')->withPivot(
            'competent',
            'write_access'
        )->using(MoneySourceUserPivot::class);
    }

    public function pinnedByUsers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'money_source_user_pinned');
    }

    public function competent(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'money_source_users')
            ->wherePivot('competent', true)->using(MoneySourceUserPivot::class);
    }

    public function moneySourceTasks(): HasMany
    {
        return $this->hasMany(MoneySourceTask::class, 'money_source_id');
    }

    public function projects(): BelongsToMany
    {
        return $this->belongsToMany(Project::class, 'money_source_project');
    }

    public function moneySourceFiles(): HasMany
    {
        return $this->hasMany(MoneySourceFile::class);
    }

    public function sumMoneySources(): HasMany
    {
        return $this->hasMany(SumMoneySource::class);
    }

    public function categories(): BelongsToMany
    {
        return $this
            ->belongsToMany(MoneySourceCategory::class, 'money_source_category_mappings')
            ->using(MoneySourceCategoryMapping::class);
    }

    public function reminder(): HasMany
    {
        return $this->hasMany(MoneySourceReminder::class);
    }

    /**
     * @return array<string, mixed>
     */
    public function toSearchableArray(): array
    {
        return [
            'name' => $this->name,
            'is_group' => $this->is_group
        ];
    }
}
