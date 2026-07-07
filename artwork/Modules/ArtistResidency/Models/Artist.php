<?php

namespace Artwork\Modules\ArtistResidency\Models;

use Artwork\Modules\Crm\Contracts\CrmEntity;
use Artwork\Modules\Crm\Traits\HasCrmContact;
use Artwork\Modules\Crm\Traits\HasCrmFields;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Artist extends Model implements CrmEntity
{
    /** @use HasFactory<\Database\Factories\ArtistFactory> */
    use HasFactory;
    use HasCrmContact;
    use HasCrmFields;

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

    public function getCrmFields(): array
    {
        return [
            'Künstler*innen Name' => 'name',
            'Vorname' => 'first_name',
            'Nachname' => 'last_name',
            'Telefon' => 'phone_number',
            'Position' => 'position',
        ];
    }

    public function getCrmDisplayName(): string
    {
        return $this->name ?: trim(($this->first_name ?? '') . ' ' . ($this->last_name ?? ''));
    }

    public function getCrmContactTypeSlug(): string
    {
        return 'artist';
    }
}
