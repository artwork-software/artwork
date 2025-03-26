<?php

namespace Artwork\Modules\Manufacturer\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Laravel\Scout\Searchable;

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
    use Searchable;

    protected $fillable = [
        'name',
        'address',
        'website',
        'customer_number',
        'contact_person',
        'phone',
        'email',
    ];

    public function searchableAs(): string
    {
        return 'manufacturers_index';
    }

    public function toSearchableArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'address' => $this->address,
            'website' => $this->website,
            'customer_number' => $this->customer_number,
            'contact_person' => $this->contact_person,
            'phone' => $this->phone,
            'email' => $this->email,
        ];
    }
}
