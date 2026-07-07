<?php

namespace Artwork\Modules\Shift\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ShiftPlanComment extends Model
{
    use HasFactory;

    protected $fillable = ['comment', 'date', 'created_by'];

    // Polymorphe Beziehung
    public function commentable()
    {
        return $this->morphTo();
    }
}
