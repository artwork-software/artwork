<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Laravel\Scout\Searchable;

class ChecklistTemplate extends Model
{
    use HasFactory;
    use Searchable;

    protected $fillable = [
        'name',
        'user_id',
    ];

    public function task_templates()
    {
        return $this->hasMany(TaskTemplate::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function departments()
    {
        return $this->belongsToMany(Department::class);
    }

    public function toSearchableArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
        ];

    }
}
