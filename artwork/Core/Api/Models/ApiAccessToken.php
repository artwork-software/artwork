<?php

namespace Artwork\Core\Api\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Laravel\Passport\Token;

/**
 * @property int $id
 * @property int $passport_token_id
 * @property string $access_token
 * @property Token $passportToken
 */
class ApiAccessToken extends Model
{
    protected $table = 'api_access_tokens';

    protected $fillable = ['passport_token_id', 'access_token'];

    public function passportToken(): BelongsTo
    {
        return $this->belongsTo(Token::class, 'passport_token_id', 'id', 'passportToken');
    }
}
