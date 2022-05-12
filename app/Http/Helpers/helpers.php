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


function history_description_change($change): string
{

    return match ($change) {
        'name' => 'Projektname wurde geändert',
        'description' => 'Kurzbeschreibung wurde geändert',
        'number_of_participants' => 'Anzahl Teilnehmer:innen geändert',
        'cost_center' => 'Kostenträger geändert',
        'sector_id'=> 'Bereich geändert',
        'category_id'=> 'Kategorie geändert',
        'genre_id'=> 'Genre geändert',
    };

}

function history_description_added($change): string
{

    return match ($change) {
        'description' => 'Kurzbeschreibung wurde hinzugefügt',
        'number_of_participants' => 'Anzahl Teilnehmer:innen hinzugefügt',
        'cost_center' => 'Kostenträger hinzugefügt',
        'sector_id'=> 'Bereich hinzugefügt',
        'category_id'=> 'Kategorie hinzugefügt',
        'genre_id'=> 'Genre hinzugefügt',
    };

}

function history_description_removed($change): string
{

    return match ($change) {
        'description' => 'Kurzbeschreibung wurde gelöscht',
        'number_of_participants' => 'Anzahl Teilnehmer:innen gelöscht',
        'cost_center' => 'Kostenträger gelöscht',
        'sector_id'=> 'Bereich gelöscht',
        'category_id'=> 'Kategorie gelöscht',
        'genre_id'=> 'Genre gelöscht',
    };

}
