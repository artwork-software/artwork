<?php

namespace Artwork\Modules\Contacts\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Contact extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'street',
        'zip_code',
        'location',
        'email',
        'phone',
        'mobile',
        'fax',
        'is_primary'
    ];

    public function contactable(): MorphTo
    {
        return $this->morphTo();
    }
}
