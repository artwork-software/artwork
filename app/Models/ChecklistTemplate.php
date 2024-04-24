<?php

namespace App\Models;

use Artwork\Modules\TaskTemplate\Models\TaskTemplate;
use Artwork\Modules\User\Models\BelongsToUser;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Laravel\Scout\Searchable;

/**
 * @property int $id
 * @property string $name
 * @property int $user_id
 * @property string $created_at
 * @property string $updated_at
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

    //@todo: fix phpcs error - refactor function name to taskTemplate
    //phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    public function task_templates()
    {
        return $this->hasMany(TaskTemplate::class);
    }

    public function users()
    {
        return $this->belongsToMany(User::class);
    }

    /**
     * @return array<string, mixed>
     */
    public function toSearchableArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
        ];
    }
}
