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

    /** Die Rollen-Vorlagen, die tickets kennt — ohne Inhaber: den vergibt nur ein Inhaber dort. */
    public const TEAM_PRESETS = ['admin', 'staff', 'door'];

    /** Die Rechtsformen, wie tickets sie kennt (`house_legal_form`). */
    public const LEGAL_FORMS = ['verein', 'ggmbh', 'gmbh', 'einzelunternehmen', 'gbr', 'oeffentlich', 'sonstige'];

    /**
     * Wer hinter dem Haus steht und wohin ausgezahlt wird. Dieselben Pflichtfelder wie in
     * tickets selbst: Register und Website sind freiwillig, von USt-IdNr. und Steuernummer
     * reicht eine. Ein angeschlossenes artwork wird nicht geprüft, aber die Angaben
     * braucht tickets für Gutschrift und Auszahlung — deshalb gehören sie in den Assistenten.
     * Der darf sie überspringen (billing = null); dann gibt tickets nichts in den Verkauf,
     * bis sie im Tab "Angaben & Bankverbindung" nachgetragen sind.
     *
     * @return array<string, mixed>
     */
    public static function billing(): array
    {
        return [
            'billing.legal_name' => 'required|string|min:2|max:200',
            'billing.legal_form' => ['required', Rule::in(self::LEGAL_FORMS)],
            'billing.street' => 'required|string|max:200',
            'billing.postal_code' => 'required|string|max:12',
            'billing.city' => 'required|string|max:120',
            'billing.country' => ['required', Rule::in(self::COUNTRIES)],
            'billing.register_number' => 'present|nullable|string|max:60',
            'billing.register_court' => 'present|nullable|string|max:120',
            'billing.vat_id' => 'required_without:billing.tax_number|nullable|string|max:20',
            'billing.tax_number' => 'required_without:billing.vat_id|nullable|string|max:30',
            'billing.contact_name' => 'required|string|max:160',
            'billing.contact_phone' => 'required|string|max:40',
            'billing.website' => 'present|nullable|url|max:200',
            'billing.account_holder' => 'required|string|max:160',
            'billing.iban' => ['required', 'string', self::ibanRule()],
        ];
    }

    /** Prüft eine eingetippte IBAN; leer lässt die Regel durch, das entscheiden required/nullable. */
    public static function ibanRule(): \Closure
    {
        return static function (string $attribute, mixed $value, \Closure $fail): void {
            if (!is_string($value) || !self::isValidIban($value)) {
                $fail(__('This IBAN is not valid.'));
            }
        };
    }

    /** Großbuchstaben ohne Leerzeichen — so speichert und vergleicht tickets die IBAN. */
    public static function normalizeIban(string $value): string
    {
        return strtoupper((string) preg_replace('/\s+/', '', $value));
    }

    /** ISO 7064 mod 97-10, wie die Bank prüft. */
    public static function isValidIban(string $value): bool
    {
        $iban = self::normalizeIban($value);

        if (preg_match('/^[A-Z]{2}\d{2}[A-Z0-9]{11,30}$/', $iban) !== 1) {
            return false;
        }

        $digits = (string) preg_replace_callback(
            '/[A-Z]/',
            static fn (array $match): string => (string) (ord($match[0]) - 55),
            substr($iban, 4) . substr($iban, 0, 4),
        );

        $remainder = 0;

        foreach (str_split($digits) as $digit) {
            $remainder = ($remainder * 10 + (int) $digit) % 97;
        }

        return $remainder === 1;
    }

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
