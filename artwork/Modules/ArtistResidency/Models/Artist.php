<?php

namespace Artwork\Modules\ArtistResidency\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Artist extends Model
{
    /** @use HasFactory<\Database\Factories\ArtistFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'civil_name',
        'phone_number',
        'position',
    ];

    protected $appends = [
        'profile_photo_url',
    ];

    public function residencies(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(ArtistResidency::class);
    }


    public function getProfilePhotoUrlAttribute(): string
    {

        return route('generate-avatar-image', ['letters' => $this->name[0] ?? 'A']);
    }
}
