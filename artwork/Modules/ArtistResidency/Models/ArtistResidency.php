<?php

namespace Artwork\Modules\ArtistResidency\Models;

use Artwork\Core\Database\Models\Model;
use Artwork\Modules\ServiceProvider\Models\ServiceProvider;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Date;

/**
 * @property string name
 * @property string civil_name
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
 * @property string description
 * @property ServiceProvider serviceProvider
 */
class ArtistResidency extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'civil_name',
        'phone_number',
        'position',
        'service_provider_id',
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
        'description',
    ];

    protected $appends = ['formatted_dates'];

    public function serviceProvider(): BelongsTo
    {
        return $this->belongsTo(
            ServiceProvider::class,
            'service_provider_id',
            'id',
            'service_provider'
        )->without(['contacts']);
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
