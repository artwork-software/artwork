<?php

namespace Artwork\Modules\ArtistResidency\Models;

use Artwork\Core\Casts\TimeWithoutSeconds;
use Artwork\Core\Database\Models\Model;
use Artwork\Modules\Accommodation\Models\Accommodation;
use Artwork\Modules\Accommodation\Models\AccommodationRoomType;
use Artwork\Modules\ArtistResidency\Models\Artist;
use Artwork\Modules\Crm\Models\CrmContact;
use Artwork\Modules\ServiceProvider\Models\ServiceProvider;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Date;

/**
 * @property string name
 * @property string first_name
 * @property string last_name
 * @property string phone_number
 * @property string position
 * @property int service_provider_id
 * @property string arrival_date
 * @property string arrival_time
 * @property string departure_date
 * @property string departure_time
 * @property int type_of_room
 * @property float cost_per_night
 * @property float daily_allowance
 * @property float additional_daily_allowance
 * @property int breakfast_count
 * @property float breakfast_deduction_per_day
 * @property string description
 * @property ServiceProvider serviceProvider
 */
class ArtistResidency extends Model
{
    use HasFactory;

    protected $fillable = [
        'artist_id',
        'artist_crm_contact_id',
        'accommodation_id',
        'accommodation_crm_contact_id',
        'project_id',
        'arrival_date',
        'arrival_time',
        'departure_date',
        'departure_time',
        'type_of_room',
        'days',
        'cost_per_night',
        'daily_allowance',
        'additional_daily_allowance',
        'breakfast_count',
        'breakfast_deduction_per_day',
        'description',
        'do_not_save_artist',
        'name',
        'first_name',
        'last_name',
        'phone_number',
        'position',
        'crm_property_overrides',
    ];

    protected $casts = [
        'arrival_date' => 'date',
        'arrival_time' => TimeWithoutSeconds::class,
        'departure_date' => 'date',
        'departure_time' => TimeWithoutSeconds::class,
        'crm_property_overrides' => 'array',
    ];

    protected $appends = ['formatted_dates', 'display_name'];

    /**
     * Returns the artist name – either from the linked Artist or from the local fields.
     */
    public function getDisplayNameAttribute(): ?string
    {
        if ($this->do_not_save_artist) {
            $fullName = trim(($this->first_name ?? '') . ' ' . ($this->last_name ?? ''));

            return $fullName ?: $this->name;
        }

        if (!empty($this->name)) {
            return $this->name;
        }

        return $this->artistContact?->display_name
            ?? $this->artist?->display_name;
    }

    public function artistContact(): BelongsTo
    {
        return $this->belongsTo(
            CrmContact::class,
            'artist_crm_contact_id',
            'id',
            'artistContact'
        );
    }

    public function accommodationContact(): BelongsTo
    {
        return $this->belongsTo(
            CrmContact::class,
            'accommodation_crm_contact_id',
            'id',
            'accommodationContact'
        );
    }

    public function accommodation(): BelongsTo
    {
        return $this->belongsTo(
            Accommodation::class,
            'accommodation_id',
            'id',
            'accommodation'
        );
    }

    // artist
    public function artist(): BelongsTo
    {
        return $this->belongsTo(
            Artist::class,
            'artist_id',
            'id',
            'artist'
        );
    }

    public function roomType(): BelongsTo
    {
        return $this->belongsTo(
            AccommodationRoomType::class,
            'type_of_room',
            'id',
            'roomType'
        );
    }

    /**
     * @return array<string, string>
     */
    public function getFormattedDatesAttribute(): array
    {
        return [
            'arrival_date' => Date::parse($this->arrival_date)->translatedFormat('d.m.Y'),
            'arrival_time' => $this->arrival_time ? Date::parse($this->arrival_time)->translatedFormat('H:i') : null,
            'departure_date' => Date::parse($this->departure_date)->translatedFormat('d.m.Y'),
            'departure_time' => $this->departure_time ? Date::parse($this->departure_time)->translatedFormat('H:i') : null,
        ];
    }
}
