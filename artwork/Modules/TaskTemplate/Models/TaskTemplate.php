<?php

namespace Artwork\Modules\TaskTemplate\Models;

use Artwork\Core\Database\Models\Model;
use Artwork\Modules\Checklist\Models\ChecklistTemplate;
use Artwork\Modules\User\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * @property int $id
 * @property string $name
 * @property string $description
 * @property int $done
 * @property int $checklist_template_id
 * @property int|null $order
 * @property int|null $deadline_days_after_creation
 * @property Collection $task_users
 * @property string $created_at
 * @property string $updated_at
 */
class TaskTemplate extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'done',
        'checklist_template_id',
        'order',
        'deadline_days_after_creation'
    ];

    protected $casts = [
        'done' => 'boolean',
        'deadline_days_after_creation' => 'integer'
    ];

    //@todo: fix phpcs error - refactor function name to checklistTemplate
    //phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    public function checklist_template(): BelongsTo
    {
        return $this->belongsTo(
            ChecklistTemplate::class,
            'checklist_template_id',
            'id',
            'checklist_templates'
        );
    }

    //@todo: fix phpcs error - refactor function name to taskUsers
    //phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    public function task_users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'task_template_user');
    }
}
