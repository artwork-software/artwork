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
        'description',
        'is_freed',
        'has_power_of_attorney',
        'amount',
        'project_id',
        'contract_type_id',
        'company_type_id',
        'ksk_liable',
        'resident_abroad',
        'currency'
    ];

    protected $guarded = [
        'id'
    ];

    protected $casts = [
        'ksk_liable' => 'boolean',
        'resident_abroad' => 'boolean',
        'is_freed' => 'boolean',
        'has_power_of_attorney' => 'boolean',
    ];

    public function project()
    {
        return $this->belongsTo(Project::class, 'project_id');
    }

    public function company_type()
    {
        return $this->belongsTo(CompanyType::class, 'company_type_id');
    }

    public function contract_type()
    {
        return $this->belongsTo(ContractType::class, 'contract_type_id');
    }

    public function accessing_users()
    {
        return $this->belongsToMany(User::class);
    }

    public function tasks()
    {
        return $this->belongsToMany(Task::class);
    }

    public function comments()
    {
        return $this->hasMany(Comment::class);
    }

}
