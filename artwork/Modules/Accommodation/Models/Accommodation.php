<?php

namespace Artwork\Modules\Accommodation\Models;

use Artwork\Modules\Contacts\Models\Traits\HasContacts;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Artwork\Modules\Accommodation\Models\Accommodation
 *
 * @property int $id
 * @property string $name
 * @property string|null $email
 * @property string|null $phone_number
 * @property string|null $street
 * @property string|null $zip_code
 * @property string|null $location
 * @property string|null $note
 * @property string|null $profile_image
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class Accommodation extends Model
{
    use HasFactory;
    use HasContacts;

    protected $fillable = [
        'name',
        'email',
        'phone_number',
        'street',
        'zip_code',
        'location',
        'note',
        'profile_image'
    ];

    protected $appends = [
        'profile_photo_url'
    ];

    public function getProfilePhotoUrlAttribute(): string
    {
        return $this->profile_image
            ? asset('storage/' . $this->profile_image)
            : route('generate-avatar-image', ['letters' => $this->name[0]]);
    }
}
