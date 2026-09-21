<?php

namespace Artwork\Modules\SageApiSettings\Models;

use Artwork\Core\Database\Models\Model;

/**
 * @property int $id
 * @property string $host
 * @property string $endpoint
 * @property string $user
 * @property string $password
 * @property string|null $bookingDate
 * @property string|null $fetchTime
 * @property bool $enabled
 * @property bool|null $verify_ssl
 * @property string $created_at
 * @property string $updated_at
 */
class SageApiSettings extends Model
{
    protected $fillable = [
        'host',
        'endpoint',
        'user',
        'password',
        'bookingDate',
        'fetchTime',
        'enabled',
        'verify_ssl',
    ];

    protected $hidden = [
        'password',
    ];

    protected $casts = [
        'enabled' => 'boolean',
        'verify_ssl' => 'boolean',
    ];

    /**
     * Nur ein explizit gespeichertes false schaltet die TLS-Verifikation ab.
     */
    public function shouldVerifySsl(): bool
    {
        return $this->verify_ssl !== false;
    }
}
