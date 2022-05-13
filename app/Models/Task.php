<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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
        'done_at'
    ];

    protected $casts = [
      "done" => 'boolean'
    ];

    public function checklist()
    {
        return $this->belongsTo(Checklist::class, 'checklist_id');
    }

    public function user_who_done() {
        return $this->belongsTo(User::class, 'user_id');
    }
}
