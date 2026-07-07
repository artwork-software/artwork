<?php

namespace Artwork\Modules\Manufacturer\Models;

use Artwork\Modules\Crm\Contracts\CrmEntity;
use Artwork\Modules\Crm\Traits\HasCrmContact;
use Artwork\Modules\Crm\Traits\HasCrmFields;
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
class Manufacturer extends Model implements CrmEntity
{
    use HasFactory;
    use Searchable;
    use HasCrmContact;
    use HasCrmFields;

    protected $fillable = [
        'name',
        'address',
        'website',
        'customer_number',
        'contact_person',
        'phone',
        'email',
    ];

    protected $appends = [
        'profile_photo_url',
    ];

    public function searchableAs(): string
    {
        return 'manufacturers_index';
    }

    public function getProfilePhotoUrlAttribute(): string
    {
        return $this->profile_photo_path
            ? asset('storage/' . $this->profile_photo_path)
            : route('generate-avatar-image', ['letters' => $this->name[0] ?? 'M']);
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

    public function getCrmFields(): array
    {
        return [
            'Email' => 'email',
            'Telefon' => 'phone',
            'Straße, Hausnummer' => 'address',
            'Website' => 'website',
            'Kundennummer' => 'customer_number',
            'Kontaktperson' => 'contact_person',
        ];
    }

    public function getCrmDisplayName(): string
    {
        return $this->name;
    }

    public function getCrmContactTypeSlug(): string
    {
        return 'manufacturer';
    }
}
