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
        'first_name',
        'last_name',
        'phone_number',
        'position',
    ];

    protected $appends = [
        'profile_photo_url',
        'display_name',
    ];

    public function residencies(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(ArtistResidency::class);
    }


    public function getDisplayNameAttribute(): string
    {
        $fullName = trim(($this->first_name ?? '') . ' ' . ($this->last_name ?? ''));

        return $fullName ?: ($this->name ?? '');
    }

    public function getProfilePhotoUrlAttribute(): string
    {

        return route('generate-avatar-image', ['letters' => $this->name[0] ?? 'A']);
    }
}
