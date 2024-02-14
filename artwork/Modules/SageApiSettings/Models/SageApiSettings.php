<?php

namespace Artwork\Modules\SageApiSettings\Models;

use Artwork\Core\Database\Models\Model;

/**
 * @property int $id
 * @property string $host
 * @property string $endpoint
 * @property string $apiKey
 * @property string $user
 * @property string $password
 * @property int $fetchTime
 * @property bool $enabled
 */
class SageApiSettings extends Model
{
    protected $fillable = [
        'host',
        'endpoint',
        'apiKey',
        'user',
        'password',
        'fetchTime',
        'enabled'
    ];

    protected $casts = [
        'enabled' => 'boolean'
    ];
}
