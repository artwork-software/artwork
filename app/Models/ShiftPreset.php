<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Laravel\Scout\Searchable;

class ShiftPreset extends Model
{
    use HasFactory;
    use Searchable;

    protected $fillable = [
        'name',
        'event_type_id'
    ];


    //protected $with = ['event_type', 'shifts', 'timeLine'];

    public function timeLine(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(PresetTimeLine::class);
    }

    public function shifts(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(PresetShift::class);
    }

    public function event_type(){
        return $this->belongsTo(EventType::class);
    }

    public function toSearchableArray(): array
    {
        return [
            'name' => $this->name,
            'event_type_id' => $this->event_type_id
        ];
    }

}
