<?php

namespace Artwork\Modules\Crm\Models;

use Artwork\Modules\Accommodation\Models\AccommodationRoomType;
use Artwork\Modules\ArtistResidency\Models\ArtistResidency;
use Artwork\Modules\Contacts\Models\Traits\HasContacts;
use Artwork\Modules\Crm\Contracts\CrmEntity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class CrmContact extends Model
{
    use HasFactory;
    use SoftDeletes;
    use HasContacts;

    protected $fillable = [
        'crm_contact_type_id',
        'display_name',
        'profile_image',
        'is_active',
        'entity_type',
        'entity_id',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    protected $appends = [
        'profile_photo_url',
    ];

    public function entity(): MorphTo
    {
        return $this->morphTo('entity');
    }

    public function getSourceEntity(): ?CrmEntity
    {
        $entity = $this->entity;

        return $entity instanceof CrmEntity ? $entity : null;
    }

    public function contactType(): BelongsTo
    {
        return $this->belongsTo(CrmContactType::class, 'crm_contact_type_id');
    }

    public function propertyValues(): HasMany
    {
        return $this->hasMany(CrmPropertyValue::class, 'crm_contact_id');
    }

    public function roomTypes(): BelongsToMany
    {
        return $this->belongsToMany(
            AccommodationRoomType::class,
            'accommodation_accommodation_room_type',
            'crm_contact_id',
            'accommodation_room_type_id'
        )->withPivot('cost_per_night');
    }

    public function artistResidencies(): HasMany
    {
        return $this->hasMany(ArtistResidency::class, 'artist_crm_contact_id');
    }

    public function accommodationResidencies(): HasMany
    {
        return $this->hasMany(ArtistResidency::class, 'accommodation_crm_contact_id');
    }

    public function getPropertyValue(string $propertyName): ?string
    {
        return $this->propertyValues()
            ->whereHas('property', fn ($q) => $q->where('name', $propertyName))
            ->first()
            ?->value;
    }

    public function getProfilePhotoUrlAttribute(): string
    {
        if ($this->profile_image) {
            return asset('storage/' . $this->profile_image);
        }

        $letter = mb_substr($this->display_name ?? 'C', 0, 1);

        return route('generate-avatar-image', ['letters' => $letter]);
    }
}
