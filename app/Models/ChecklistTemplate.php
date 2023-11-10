<?php

namespace App\Models;

use Artwork\Modules\User\Models\BelongsToUser;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Laravel\Scout\Searchable;

/**
 * @property int $id
 * @property string $name
 * @property int $user_id
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 *
 * @property TaskTemplate $task_templates
 * @property User $user
 * @property \Illuminate\Database\Eloquent\Collection<Department> $departments
 */
class ChecklistTemplate extends Model
{
    use HasFactory;
    use BelongsToUser;
    use Searchable;

    protected $fillable = [
        'name',
        'user_id',
    ];

    public function task_templates()
    {
        return $this->hasMany(TaskTemplate::class);
    }

    public function users()
    {
        return $this->belongsToMany(User::class);
    }

    public function toSearchableArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
        ];
    }
}
