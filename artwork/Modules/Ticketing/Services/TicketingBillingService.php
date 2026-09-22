<?php

namespace Artwork\Modules\Ticketing\Services;

use Artwork\Modules\Ticketing\Exceptions\TicketingConnectionException;
use Artwork\Modules\Ticketing\Http\Requests\TicketingDraftRules;
use Artwork\Modules\Ticketing\Models\TicketingConnection;

/**
 * Rechtliche Angaben und Bankverbindung des Ticket-Hauses, aus Sicht dieses artworks: der
 * Assistent darf sie überspringen, der Tab "Angaben & Bankverbindung" trägt sie nach. Gespeichert
 * wird nur drüben; hier gibt es keinen Abzug davon. Die IBAN kommt nie zurück, nur ihre letzten
 * vier Stellen — leer lassen heißt beim Speichern "die hinterlegte behalten".
 */
class TicketingBillingService
{
    public function __construct(private readonly TicketsClient $tickets)
    {
    }

    /** @return array{profile: array<string, string>, iban_last4: string|null, legal_complete: bool, bank_complete: bool} */
    public function status(TicketingConnection $connection): array
    {
        return self::fromTickets($this->tickets->get($connection, '/house/billing'));
    }

    /** Beide Blöcke vollständig — erst dann gibt tickets Termine in den Verkauf und zahlt aus. */
    public function isComplete(TicketingConnection $connection): bool
    {
        $status = $this->status($connection);

        return $status['legal_complete'] && $status['bank_complete'];
    }

    /** Für eine Warnung neben anderem Inhalt: null, wenn tickets nicht antwortet — dann keine Warnung. */
    public function completeness(TicketingConnection $connection): ?bool
    {
        try {
            return $this->isComplete($connection);
        } catch (TicketingConnectionException) {
            return null;
        }
    }

    /**
     * @param array<string, string|null> $billing
     * @return array{profile: array<string, string>, iban_last4: string|null, legal_complete: bool, bank_complete: bool}
     */
    public function save(TicketingConnection $connection, array $billing): array
    {
        return self::fromTickets($this->tickets->put($connection, '/house/billing', self::payload($billing, '')));
    }

    /**
     * Die Angaben, wie tickets sie erwartet: camelCase, IBAN ohne Leerzeichen. Was leer ist, wird
     * beim Anlegen des Hauses als null geschickt, beim Nachtragen als leerer Text — die beiden
     * Endpunkte prüfen unterschiedlich streng.
     *
     * @param array<string, string|null> $billing
     * @return array<string, string|null>
     */
    public static function payload(array $billing, ?string $empty = null): array
    {
        $text = static fn (string $key): ?string => ($billing[$key] ?? null) === '' || ($billing[$key] ?? null) === null
            ? $empty
            : $billing[$key];

        return [
            'legalName' => $text('legal_name'),
            'legalForm' => $text('legal_form'),
            'street' => $text('street'),
            'postalCode' => $text('postal_code'),
            'city' => $text('city'),
            'country' => $text('country'),
            'registerNumber' => $text('register_number'),
            'registerCourt' => $text('register_court'),
            'vatId' => $text('vat_id'),
            'taxNumber' => $text('tax_number'),
            'contactName' => $text('contact_name'),
            'contactPhone' => $text('contact_phone'),
            'website' => $text('website'),
            'accountHolder' => $text('account_holder'),
            'iban' => TicketingDraftRules::normalizeIban((string) ($billing['iban'] ?? '')) ?: $empty,
        ];
    }

    /**
     * Die Antwort von tickets als Entwurf, wie das Formular ihn bearbeitet: snake_case, leer statt null.
     *
     * @param array<string, mixed> $response
     * @return array{profile: array<string, string>, iban_last4: string|null, legal_complete: bool, bank_complete: bool}
     */
    private static function fromTickets(array $response): array
    {
        $profile = is_array($response['profile'] ?? null) ? $response['profile'] : [];
        $text = static fn (string $key): string => (string) ($profile[$key] ?? '');

        return [
            'profile' => [
                'legal_name' => $text('legalName'),
                'legal_form' => $text('legalForm'),
                'street' => $text('street'),
                'postal_code' => $text('postalCode'),
                'city' => $text('city'),
                'country' => $text('country') ?: 'DE',
                'register_number' => $text('registerNumber'),
                'register_court' => $text('registerCourt'),
                'vat_id' => $text('vatId'),
                'tax_number' => $text('taxNumber'),
                'contact_name' => $text('contactName'),
                'contact_phone' => $text('contactPhone'),
                'website' => $text('website'),
                'account_holder' => $text('accountHolder'),
                'iban' => '',
            ],
            'iban_last4' => $text('ibanLast4') ?: null,
            'legal_complete' => (bool) ($response['legalComplete'] ?? false),
            'bank_complete' => (bool) ($response['bankComplete'] ?? false),
        ];
    }
}
