<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use function Termwind\render;

class Freelancer extends Model
{
    use HasFactory;


    protected $fillable = [
        'position',
        'profile_image',
        'first_name',
        'last_name',
        'email',
        'phone_number',
        'street',
        'zip_code',
        'location',
        'note',
    ];

    protected $appends = [
        'name'
    ];

    public function getNameAttribute(){
        return $this->first_name . ' ' . $this->last_name;
    }

}
