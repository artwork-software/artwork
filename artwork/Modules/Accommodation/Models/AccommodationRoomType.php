<?php

namespace Artwork\Modules\Accommodation\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AccommodationRoomType extends Model
{
    /** @use HasFactory<\Database\Factories\AccommodationRoomTypeFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
    ];

    // mapping room types to accommodation (many to many)
    public function accommodations(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(
            Accommodation::class,
            'accommodation_accommodation_room_type',
            'accommodation_room_type_id',
            'accommodation_id'
        );
    }
}
