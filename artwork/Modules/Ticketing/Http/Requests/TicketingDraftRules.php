<?php

namespace Artwork\Modules\Ticketing\Http\Requests;

use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * Die Regeln für Räume und Ermäßigungen, wie sie der Verbindungs-Assistent und die
 * beiden Abgleich-Tabs gleichermaßen prüfen.
 */
final class TicketingDraftRules
{
    /** Dieselbe Liste wie in artwork tickets: die Nachbarländer, die ein Haus tatsächlich bespielt. */
    public const COUNTRIES = ['DE', 'AT', 'CH', 'LI', 'LU', 'NL', 'BE', 'FR', 'DK', 'PL', 'CZ', 'IT'];

    /** @return array<string, mixed> */
    public static function rooms(): array
    {
        return [
            'rooms' => 'present|array|max:200',
            'rooms.*.id' => 'required|integer|distinct|exists:rooms,id',
            // Leere Felder kommen als null an (ConvertEmptyStringsToNull).
            'rooms.*.street' => 'present|nullable|string|max:160',
            'rooms.*.postal_code' => 'present|nullable|string|max:16',
            'rooms.*.city' => 'present|nullable|string|max:120',
            'rooms.*.country' => ['required', Rule::in(self::COUNTRIES)],
            'rooms.*.zones' => 'required|array|min:1|max:50',
            'rooms.*.zones.*.name' => 'required|string|max:60',
            'rooms.*.zones.*.capacity' => 'required|integer|min:0|max:1000000',
            'rooms.*.zones.*.default_price_cents' => 'nullable|integer|min:0',
        ];
    }

    /** @return array<string, mixed> */
    public static function reductions(): array
    {
        return [
            'reductions' => 'present|array|max:50',
            'reductions.*.name' => 'required|string|max:60',
            'reductions.*.kind' => ['required', Rule::in(['percent', 'fixed'])],
            'reductions.*.value' => 'required|integer|min:1',
            'reductions.*.requires_proof' => 'required|boolean',
            'reductions.*.default_enabled' => 'required|boolean',
        ];
    }

    /** @param list<array<string, mixed>> $reductions */
    public static function checkReductions(Validator $validator, array $reductions): void
    {
        foreach ($reductions as $index => $reduction) {
            if (($reduction['kind'] ?? null) === 'percent' && (int) ($reduction['value'] ?? 0) > 10000) {
                $validator->errors()->add("reductions.$index.value", __('A percent reduction cannot exceed 100 %.'));
            }
        }
    }
}
