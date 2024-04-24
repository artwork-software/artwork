<?php

use Artwork\Modules\Invitation\Models\Invitation;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

//phpcs:ignore SlevomatCodingStandard.TypeHints.ReturnTypeHint.MissingTraversableTypeHintSpecification
function createToken(): array
{
    do {
        $tokenPlain = Str::random(20);
        $hashedToken = Hash::make($tokenPlain);
    } while (Invitation::where('token', $hashedToken)->first());

    return [
        'plain' => $tokenPlain,
        'hash' => $hashedToken
    ];
}
