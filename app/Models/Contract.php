<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Contract extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'basename',
        'contract_partner',
        'amount',
        'project_id',
        'description',
        'ksk_liable',
        'resident_abroad',
        'legal_form',
        'type'
    ];

    protected $guarded = [
        'id'
    ];

    protected $casts = [
        'ksk_liable' => 'boolean',
        'resident_abroad' => 'boolean'
    ];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function accessing_users()
    {
        return $this->belongsToMany(User::class);
    }

}
