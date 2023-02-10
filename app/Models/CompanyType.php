<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CompanyType extends Model
{
    use HasFactory;

    protected $fillable = [
      'name'
    ];

    public function contracts()
    {
        return $this->belongsToMany(Contract::class);
    }
}
