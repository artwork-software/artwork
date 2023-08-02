<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FreelancerVacation extends Model
{
    use HasFactory;

    protected $fillable = [
        'freelancer_id',
        'from',
        'until'
    ];

    public function freelancer(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Freelancer::class);
    }

}
