<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MoneySourceTask extends Model
{
    use HasFactory;


    protected $fillable = [
        'money_source_id',
        'name',
        'description',
        'deadline',
        'creator'
    ];


    public function money_source(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(MoneySource::class);
    }

    public function money_source_task_users(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(User::class, 'money_source_task_user', 'task_id');
    }
}
