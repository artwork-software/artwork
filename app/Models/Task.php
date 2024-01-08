<?php

namespace App\Models;

use Artwork\Modules\Checklist\Models\BelongsToChecklist;
use Artwork\Modules\Checklist\Models\Checklist;
use Artwork\Modules\Department\Models\Department;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $name
 * @property string $description
 * @property bool $done
 * @property Carbon $deadline
 * @property Carbon $done_at
 * @property int $order
 * @property int $checklist_id
 * @property int $user_id
 * @property int $contract_id
 * @property string $created_at
 * @property string $updated_at
 */
class Task extends Model
{
    use HasFactory;
    use BelongsToChecklist;

    protected $fillable = [
        'name',
        'description',
        'deadline',
        'done',
        'checklist_id',
        'order',
        'user_id',
        'done_at',
        'contract_id'
    ];

    protected $casts = [
        'done' => 'boolean',
    ];

    protected $dates = [
        'done_at',
        'deadline',
    ];

    public function contract(): BelongsTo
    {
        return $this->belongsTo(Contract::class, 'contract_id');
    }

    //@todo: fix phpcs error - refactor function name to taskUsers
    //phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    public function task_users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'task_user', 'task_id');
    }

    //@todo: fix phpcs error - refactor function name to userWhoDone
    //phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    public function user_who_done(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'id', 'user_who_done');
    }

    public function checklistDepartments(): BelongsToMany
    {
        return $this->belongsToMany(
            Department::class,
            'checklist_department',
            'checklist_id',
            'department_id',
            'checklist_id'
        );
    }

    //@todo: fix phpcs error - refactor function name to moneySourceTask
    //phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    public function money_source_task()
    {
        return $this->belongsToMany(MoneySourceTask::class);
    }
}
