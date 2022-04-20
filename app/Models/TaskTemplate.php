<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TaskTemplate extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'done',
        'checklist_template_id'
    ];

    public function checklist_template()
    {
        return $this->belongsTo(ChecklistTemplate::class, 'checklist_template_id');
    }
}
