<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * @property int $project_id
 * @property string $name
 * @property string $subName
 * @property string $type
 * @property int $linked_first_column
 * @property int $linked_second_column
 * @property string $color
 * @property boolean $is_locked
 */
class Column extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_id',
        'name',
        'subName',
        'type',
        'linked_first_column',
        'linked_second_column',
        'color',
        'is_locked'
    ];

    protected $casts = [
        'is_locked' => 'boolean',
    ];

    public function subPositionRows(): BelongsToMany
    {
        return $this->belongsToMany(SubPositionRow::class);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function cells(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(ColumnCell::class, 'column_id');
    }

}
