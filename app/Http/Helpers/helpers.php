<?php

use App\Models\Invitation;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

function createToken(): array
{
    do {
        //generate a random string using Laravel's str_random helper
        $tokenPlain = Str::random(20);
        $hashedToken = Hash::make($tokenPlain);

    } //check if the token already exists and if it does, try again
    while (Invitation::where('token', $hashedToken)->first());

    return [
        'plain' => $tokenPlain,
        'hash' => $hashedToken
    ];
}
