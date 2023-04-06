<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * @property  int $id
 * @property  string $name
 * @property  string $description
 * @property  bool $done
 * @property  \Illuminate\Support\Carbon $done_at
 * @property  string $order
 * @property  int $checklist_id
 * @property  int $user_id
 * @property  \Illuminate\Support\Carbon $deadline
 * @property  \Illuminate\Support\Carbon $created_at
 * @property  \Illuminate\Support\Carbon $updated_at
 *
 * @property Checklist $checklist
 * @property User $user_who_done
 */
class Task extends Model
{
    use HasFactory;

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

    public function checklist(): BelongsTo
    {
        return $this->belongsTo(Checklist::class, 'checklist_id');
    }

    public function task_users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'task_user', 'task_id');
    }

    public function user_who_done(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function checklistDepartments(): BelongsToMany
    {
        return $this->belongsToMany(Department::class, 'checklist_department', 'checklist_id', 'department_id', 'checklist_id');
    }

    public function money_source_task(){
        return $this->belongsToMany(MoneySourceTask::class);
    }
}
