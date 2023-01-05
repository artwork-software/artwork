<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Column extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_id',
        'name',
        'subName',
        'type',
        'linked_first_column',
        'linked_second_column'
    ];

    public function subPositionRows(): BelongsToMany
    {
        return $this->belongsToMany(SubPositionRow::class);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function cell()
    {
        return $this->hasOne(ColumnCell::class, 'column_id');
    }

}
