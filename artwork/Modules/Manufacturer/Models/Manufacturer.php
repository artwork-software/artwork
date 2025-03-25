<?php

namespace Artwork\Modules\Manufacturer\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property string name
 * @property string address
 * @property string website
 * @property string customer_number
 * @property string contact_person
 * @property string phone
 * @property string email
 */
class Manufacturer extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'address',
        'website',
        'customer_number',
        'contact_person',
        'phone',
        'email',
    ];
}
