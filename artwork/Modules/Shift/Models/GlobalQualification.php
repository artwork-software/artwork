<?php

namespace Artwork\Modules\Shift\Models;

use Artwork\Modules\User\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * * @property int $id
 * * @property string $name
 * * @property string $icon
 * * @property \Illuminate\Support\Carbon|null $created_at
 * * @property \Illuminate\Support\Carbon|null $updated_at
 * * @property-read \Illuminate\Database\Eloquent\Collection<int, User> $users
 * * @property-read int|null $users_count
 */
class GlobalQualification extends Model
{
    /** @use HasFactory<\Database\Factories\GlobalQualificationFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'icon',
    ];

    public function users(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(
            User::class,
            'user_global_qualifications',
            'user_id',
            'global_qualification_id'
        );
    }
}
