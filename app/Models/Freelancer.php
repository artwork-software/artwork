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


    /*
     *  $table->string('position');
            $table->string('profile_image');
            $table->string('first_name');
            $table->string('last_name');
            $table->string('email');
            $table->string('phone_number');
            $table->string('street');
            $table->string('zip_code');
            $table->string('location');
            $table->string('note', 500);
     */
}
