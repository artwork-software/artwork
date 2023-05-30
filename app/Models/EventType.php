<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $name
 * @property string $svg_name
 * @property string $abbreviation
 * @property bool $project_mandatory
 * @property bool $individual_name
 * @property string $created_at
 * @property string $updated_at
 *
 * @property \Illuminate\Database\Eloquent\Collection<\App\Models\Event> $events
 */
class EventType extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'svg_name',
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

    public function events()
    {
        return $this->hasMany(event::class);
    }
}
