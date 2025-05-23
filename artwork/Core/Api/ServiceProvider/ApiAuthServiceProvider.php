<?php

namespace Artwork\Core\Api\ServiceProvider;

use Artwork\Core\Api\Models\ApiAccessToken;
use Illuminate\Support\ServiceProvider;
use Laravel\Passport\Token;

class ApiAuthServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Token::resolveRelationUsing('apiAccessToken', function ($token) {
            return $token->hasOne(ApiAccessToken::class, 'passport_token_id');
        });

    }
}
