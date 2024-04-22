<?php

namespace App\Models;

use Artwork\Modules\Event\Models\Event;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $name
 * @property bool $project_mandatory
 * @property bool $individual_name
 * @property string $abbreviation
 * @property bool $relevant_for_shift
 * @property string $created_at
 * @property string $updated_at
 */
class EventType extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'hex_code',
        'project_mandatory',
        'individual_name',
        'abbreviation',
        'relevant_for_shift'
    ];

    protected $casts = [
        'project_mandatory' => 'boolean',
        'individual_name' => 'boolean',
        'relevant_for_shift' => 'boolean',
    ];

    public function events(): HasMany
    {
        return $this->hasMany(Event::class);
    }
}
