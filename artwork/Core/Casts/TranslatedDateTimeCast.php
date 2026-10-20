<?php

namespace Artwork\Core\Casts;

use Carbon\Carbon;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;

class TranslatedDateTimeCast implements CastsAttributes
{
    /**
     * Eloquent cached beim Setzen eines Objekts (Carbon, z. B. Zeitstempel bei create()) genau dieses Objekt:
     * direkt nach dem Anlegen lieferte das Attribut ein Carbon, nach dem Laden den übersetzten String. Code, der
     * nur am frischen Model getestet war (toIso8601String() in den Verifikations-DTOs), brach erst an geladenen
     * Daten; Broadcasts direkt nach create() schickten ISO- statt Anzeigeformat.
     */
    public bool $withoutObjectCaching = true;

    /**
     * Cast the given value.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function get(Model $model, string $key, mixed $value, array $attributes): mixed
    {
        if (is_null($value)) {
            return null;
        }
        // toArray() reicht Zeitstempel-Spalten (created_at, updated_at, deleted_at) vor dem Cast als UTC-ISO-String
        // durch; ohne Umrechnung zeigte die serialisierte Anzeige die UTC-Uhrzeit statt der Ortszeit
        return Carbon::parse($value)->setTimezone(config('app.timezone'))->translatedFormat('l, d. F Y H:i');
    }

    /**
     * Prepare the given value for storage.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function set(Model $model, string $key, mixed $value, array $attributes): mixed
    {
        return $value;
    }
}
