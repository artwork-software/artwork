<?php

namespace Artwork\Core\Casts;

use Carbon\Carbon;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;

class TimeWithoutSeconds implements CastsAttributes
{
    //phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundInImplementedInterfaceAfterLastUsed, Generic.CodeAnalysis.UnusedFunctionParameter.FoundInImplementedInterfaceBeforeLastUsed
    public function get($model, string $key, mixed $value, array $attributes): ?string
    {
        if (is_null($value)) {
            return null;
        }
        // Reine Uhrzeit direkt kürzen: Carbon::parse('02:30') setzt das heutige Datum ein und macht am Tag der
        // Sommerzeitumstellung 03:30 daraus (Schicht zählte dann 60 min zu viel)
        if (is_string($value) && preg_match('/^(\d{1,2}):(\d{2})(?::\d{2})?$/', $value, $matches) === 1) {
            return sprintf('%02d:%02d', (int) $matches[1], (int) $matches[2]);
        }
        return Carbon::parse($value)->format('H:i');
    }

    //phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundInImplementedInterfaceAfterLastUsed, Generic.CodeAnalysis.UnusedFunctionParameter.FoundInImplementedInterfaceBeforeLastUsed
    public function set($model, string $key, mixed $value, array $attributes): mixed
    {
        if ($value instanceof \DateTimeInterface) {
            return $value->format('H:i:s');
        }

        return $value;
    }
}
