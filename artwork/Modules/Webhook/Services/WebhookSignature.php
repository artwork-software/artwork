<?php

namespace Artwork\Modules\Webhook\Services;

/**
 * HMAC-Signatur für ausgehende Webhooks.
 *
 * Signiert wird "<timestamp>.<body>", nicht der Body allein. Der Zeitstempel ist Teil der
 * signierten Nachricht und wird als eigener Header mitgeschickt; der Empfänger kann damit alte,
 * abgefangene Zustellungen ablehnen, ohne dass ein Angreifer den Zeitstempel fälschen könnte.
 */
class WebhookSignature
{
    public const HEADER_SIGNATURE = 'X-Artwork-Signature';
    public const HEADER_TIMESTAMP = 'X-Artwork-Timestamp';
    public const HEADER_EVENT = 'X-Artwork-Event';
    public const HEADER_DELIVERY = 'X-Artwork-Delivery-Id';

    public function sign(string $body, string $secret, int $timestamp): string
    {
        return 'sha256=' . hash_hmac('sha256', $timestamp . '.' . $body, $secret);
    }

    /**
     * Gegenstück zu sign(); wird von den Tests genutzt und dokumentiert zugleich, wie ein
     * Empfänger prüfen muss.
     */
    public function verify(string $body, string $secret, int $timestamp, string $signature): bool
    {
        return hash_equals($this->sign($body, $secret, $timestamp), $signature);
    }
}
