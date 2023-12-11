<?php

namespace App\Models;

use Antonrom\ModelChangesHistory\Traits\HasChangesHistory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Date;
use Laravel\Scout\Searchable;

/**
 * @property int $id
 * @property string $name
 * @property float $amount
 * @property Date $start_date
 * @property Date $end_date
 * @property string $source_name
 * @property string $description
 * @property boolean $is_group
 * @property array $users
 * @property int $group_id
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


    public function users()
    {
        return $this->belongsToMany(User::class, 'money_source_users')->withPivot(
            'competent',
            'write_access'
        )->using(MoneySourceUserPivot::class);
    }

    public function pinnedByUsers()
    {
        return $this->belongsToMany(User::class, 'money_source_user_pinned')->using(MoneySourceUserPinned::class);
    }

    public function competent(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(User::class, 'money_source_users')->wherePivot('competent', true)->using(MoneySourceUserPivot::class);
    }

    public function money_source_tasks(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(MoneySourceTask::class, 'money_source_id');
    }

    public function projects()
    {
        return $this->belongsToMany(Project::class, 'money_source_project');
    }

    public function toSearchableArray(): array
    {
        return [
            'name' => $this->name,
            'is_group' => $this->is_group
        ];
    }
    public function money_source_files()
    {
        return $this->hasMany(MoneySourceFile::class);
    }

    public function sumMoneySources(): HasMany
    {
        return $this->hasMany(SumMoneySource::class);
    }
}
