<?php

namespace Artwork\Modules\Ticketing\Services;

use Artwork\Modules\Ticketing\Exceptions\TicketingConnectionException;
use Artwork\Modules\Ticketing\Models\TicketingConnection;
use Artwork\Modules\User\Models\User;
use Illuminate\Http\UploadedFile;

/**
 * Was das Ticket-Haus braucht, bevor es verkauft, aus Sicht dieses artworks: rechtliche Angaben,
 * AGB, Datenschutzerklärung und Impressum des Shops (Link oder PDF), das Auszahlungskonto und die unterschriebenen
 * Nutzungsbedingungen von tickets. Der Assistent darf die Angaben überspringen, der Tab
 * "Angaben & Auszahlung" trägt sie nach. Gespeichert wird nur drüben. Das Auszahlungskonto prüft
 * Stripe in seinem eigenen Formular, das der Tab mit einer Sitzung von tickets einbettet.
 *
 * @phpstan-type BillingStatus array{
 *     profile: array<string, string>,
 *     legal_complete: bool,
 *     shop_legal_files: array<string, array{file_name: string, url: string}|null>,
 *     shop_legal_complete: bool,
 *     payout_account: 'open'|'review'|'verified',
 *     stripe_key: string|null,
 *     platform_terms: array{accepted: bool, terms_url: string, dpa_url: string},
 * }
 */
class TicketingBillingService
{
    /** Die Rechtstexte des Shops; je einer kommt als `{name}_url` oder `{name}_file`. */
    public const LEGAL_DOCUMENTS = ['terms', 'privacy', 'imprint'];

    public function __construct(private readonly TicketsClient $tickets)
    {
    }

    /** @return BillingStatus */
    public function status(TicketingConnection $connection): array
    {
        return self::fromTickets($this->tickets->get($connection, '/house/billing'));
    }

    public function isComplete(TicketingConnection $connection): bool
    {
        return self::complete($this->status($connection));
    }

    /**
     * Alles beisammen, von Stripe verifiziert und unterschrieben — erst dann verkauft das Haus.
     *
     * @param BillingStatus $status
     */
    public static function complete(array $status): bool
    {
        return $status['legal_complete']
            && $status['shop_legal_complete']
            && $status['payout_account'] === 'verified'
            && $status['platform_terms']['accepted'];
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
     * Erst die PDFs, dann die Felder: ein Upload ersetzt den Link, und das dafür geleerte Linkfeld
     * darf das Dokument nicht vorher schon entfernen.
     *
     * @param array<string, string|UploadedFile|null> $billing
     * @return BillingStatus
     */
    public function save(TicketingConnection $connection, array $billing): array
    {
        $this->uploadLegalDocuments($connection, $billing);

        return self::fromTickets($this->tickets->put($connection, '/house/billing', self::payload($billing, '')));
    }

    /** @param array<string, string|UploadedFile|null> $billing */
    public function uploadLegalDocuments(TicketingConnection $connection, array $billing): void
    {
        foreach (self::LEGAL_DOCUMENTS as $document) {
            $file = $billing["{$document}_file"] ?? null;

            if ($file instanceof UploadedFile) {
                $this->tickets->putFile(
                    $connection,
                    "/house/legal-documents/$document",
                    $file->getContent(),
                    (string) $file->getMimeType(),
                    $file->getClientOriginalName(),
                );
            }
        }
    }

    /**
     * Nimmt das PDF weg; tickets lehnt ab, wenn das Haus damit ohne den Text verkaufen würde.
     *
     * @return BillingStatus
     */
    public function removeLegalDocument(TicketingConnection $connection, string $document): array
    {
        return self::fromTickets($this->tickets->delete($connection, "/house/legal-documents/$document"));
    }

    /**
     * Neue Nutzungsbedingungen von tickets, angenommen von der Person, die hier den Haken setzt.
     *
     * @return BillingStatus
     */
    public function acceptPlatformTerms(TicketingConnection $connection, User $user): array
    {
        return self::fromTickets($this->tickets->post($connection, '/house/platform-terms', [
            'acceptedBy' => ['email' => $user->email, 'name' => $user->full_name],
        ]));
    }

    /**
     * Das Geheimnis für Stripes Formular, für jede Sitzung neu. Das Stripe-Konto entsteht dabei
     * beim ersten Mal; wer das Formular ausfüllt, ist sein Kontakt, solange das Haus keinen hat.
     */
    public function stripeSession(TicketingConnection $connection, string $email): string
    {
        // Das Anlegen des Kontos fragt Stripe mehrmals hintereinander; 10 Sekunden reichen dafür nicht.
        $session = $this->tickets->post($connection, '/house/stripe-session', ['email' => $email], timeout: 30);

        return (string) $session['clientSecret'];
    }

    /**
     * Die Angaben, wie tickets sie erwartet: camelCase. Was leer ist, wird
     * beim Anlegen des Hauses als null geschickt, beim Nachtragen als leerer Text — die beiden
     * Endpunkte prüfen unterschiedlich streng.
     *
     * @param array<string, string|UploadedFile|null> $billing
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
            'termsUrl' => $text('terms_url'),
            'privacyUrl' => $text('privacy_url'),
            'imprintUrl' => $text('imprint_url'),
        ];
    }

    /**
     * Die Antwort von tickets als Entwurf, wie das Formular ihn bearbeitet: snake_case, leer statt null.
     *
     * @param array<string, mixed> $response
     * @return BillingStatus
     */
    private static function fromTickets(array $response): array
    {
        $profile = is_array($response['profile'] ?? null) ? $response['profile'] : [];
        $text = static fn (string $key): string => (string) ($profile[$key] ?? '');
        $files = is_array($response['shopLegalFiles'] ?? null) ? $response['shopLegalFiles'] : [];
        $file = static fn (string $document): ?array => is_array($files[$document] ?? null)
            ? ['file_name' => (string) $files[$document]['fileName'], 'url' => (string) $files[$document]['url']]
            : null;

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
                'terms_url' => $text('termsUrl'),
                'privacy_url' => $text('privacyUrl'),
                'imprint_url' => $text('imprintUrl'),
            ],
            'legal_complete' => (bool) ($response['legalComplete'] ?? false),
            'shop_legal_files' => array_combine(self::LEGAL_DOCUMENTS, array_map($file, self::LEGAL_DOCUMENTS)),
            'shop_legal_complete' => (bool) ($response['shopLegalComplete'] ?? false),
            'payout_account' => match ($response['payoutAccount'] ?? null) {
                'review' => 'review',
                'verified' => 'verified',
                default => 'open',
            },
            'stripe_key' => is_string($response['stripePublishableKey'] ?? null)
                ? $response['stripePublishableKey']
                : null,
            'platform_terms' => [
                'accepted' => (bool) ($response['platformTerms']['accepted'] ?? false),
                'terms_url' => (string) ($response['platformTerms']['termsUrl'] ?? ''),
                'dpa_url' => (string) ($response['platformTerms']['dpaUrl'] ?? ''),
            ],
        ];
    }
}
