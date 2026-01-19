<?php

namespace Artwork\Core\Api\Models;

use Artwork\Core\Database\Models\Model;
use Carbon\Carbon;

/**
 * @property int $id
 * @property int $token_id
 * @property string $url
 * @property string $api_key
 * @property string $method
 * @property string $ip
 * @property string|null $payload
 * @property string $user_agent
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
class ApiLog extends Model
{
    protected $table = 'api_log';

    protected $guarded = [];

}
