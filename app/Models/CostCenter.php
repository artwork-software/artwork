<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CostCenter extends Model
{
    use HasFactory;

    protected $fillable = [
      'name',
      'description',
      'project_id'
    ];

    public function project()
    {
        return $this->belongsTo(Project::class, 'project_id');
    }
}
