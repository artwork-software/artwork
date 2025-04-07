<?php

namespace Artwork\Modules\EventType\Models;

use Artwork\Modules\Event\Models\Event;
use Artwork\Modules\User\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Artwork\Core\Database\Models\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $name
 * @property bool $project_mandatory
 * @property bool $individual_name
 * @property bool $fallback_type
 * @property string $abbreviation
 * @property bool $relevant_for_shift
 * @property bool $relevant_for_inventory
 * @property bool $relevant_for_project_period
 * @property string $hex_code
 * @property int $specific_verifier_id
 * @property string $verification_mode
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
        'relevant_for_shift',
        'relevant_for_inventory',
        'relevant_for_project_period',
        'specific_verifier_id',
        'verification_mode',
    ];

    protected $casts = [
        'project_mandatory' => 'boolean',
        'individual_name' => 'boolean',
        'relevant_for_shift' => 'boolean',
        'fallback_type' => 'boolean',
        'relevant_for_inventory' => 'boolean',
        'relevant_for_project_period' => 'boolean'
    ];

    public function events(): HasMany
    {
        return $this->hasMany(Event::class);
    }

    public function verifiers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'event_type_user');
    }

    public function specificVerifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'specific_verifier_id', 'id', 'users');
    }
}
