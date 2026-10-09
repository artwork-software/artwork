<?php

namespace Artwork\Modules\Ticketing\Services;

use Artwork\Modules\Ticketing\Exceptions\TicketingConnectionException;
use Artwork\Modules\Ticketing\Models\TicketingConnection;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

/**
 * HTTP zu Artwork-Tickets. Zwei Ausweise: der Provisionierungsschlüssel der Plattform darf Häuser
 * anlegen und Adressen prüfen, der Hausschlüssel alles innerhalb des verbundenen Hauses.
 * Nicht erreichbar oder abgelehnt heißt hier immer TicketingConnectionException mit der
 * Meldung, die tickets gibt.
 */
class TicketsClient
{
    /**
     * @param array<string, mixed> $query
     * @return array<string, mixed>
     */
    public function provisioningGet(string $path, array $query = []): array
    {
        return $this->send(fn (): Response => $this->provisioning()->get($this->url($path), $query));
    }

    /**
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    public function provisioningPost(string $path, array $payload): array
    {
        return $this->send(fn (): Response => $this->provisioning()->post($this->url($path), $payload));
    }

    /**
     * @param array<string, mixed> $query
     * @return array<string, mixed>
     */
    public function get(TicketingConnection $connection, string $path, array $query = []): array
    {
        // Ein leeres query-Array würde die Abfrage überschreiben, die schon im Pfad steht.
        return $this->send(fn (): Response => $query === []
            ? $this->house($connection)->get($this->url($path))
            : $this->house($connection)->get($this->url($path), $query));
    }

    /**
     * @param array<string, mixed> $payload
     * @param int $timeout Sekunden; länger nur für Aufrufe, hinter denen tickets selbst mehrfach Stripe fragt.
     * @return array<string, mixed>
     */
    public function post(TicketingConnection $connection, string $path, array $payload = [], int $timeout = 10): array
    {
        return $this->send(
            fn (): Response => $this->house($connection)->timeout($timeout)->post($this->url($path), $payload)
        );
    }

    /**
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    public function put(TicketingConnection $connection, string $path, array $payload): array
    {
        return $this->send(fn (): Response => $this->house($connection)->put($this->url($path), $payload));
    }

    /**
     * Rohe Bytes, etwa ein Bild: der Inhaltstyp steht im Header, der Dateiname in x-file-name.
     *
     * @return array<string, mixed>
     */
    public function putFile(
        TicketingConnection $connection,
        string $path,
        string $contents,
        string $contentType,
        string $fileName,
    ): array {
        return $this->sendFile('PUT', $connection, $path, $contents, $contentType, $fileName);
    }

    /**
     * Wie putFile, legt aber ein weiteres an statt eines zu ersetzen.
     *
     * @return array<string, mixed>
     */
    public function postFile(
        TicketingConnection $connection,
        string $path,
        string $contents,
        string $contentType,
        string $fileName,
    ): array {
        return $this->sendFile('POST', $connection, $path, $contents, $contentType, $fileName);
    }

    /** @return array<string, mixed> */
    public function delete(TicketingConnection $connection, string $path): array
    {
        return $this->send(fn (): Response => $this->house($connection)->delete($this->url($path)));
    }

    private function provisioning(): PendingRequest
    {
        return $this->request()->withToken((string) config('services.tickets.provisioning_secret'));
    }

    private function house(TicketingConnection $connection): PendingRequest
    {
        return $this->request()->withHeader('x-api-key', $connection->api_key);
    }

    private function request(): PendingRequest
    {
        return Http::acceptJson()->connectTimeout(3)->timeout(10);
    }

    private function url(string $path): string
    {
        return rtrim((string) config('services.tickets.url'), '/') . '/api/integration/v1' . $path;
    }

    /**
     * @param \Closure(): Response $send
     * @return array<string, mixed>
     */
    private function send(\Closure $send): array
    {
        try {
            $response = $send();
        } catch (ConnectionException $exception) {
            throw TicketingConnectionException::transient(__('Artwork-Tickets could not be reached.'), $exception);
        }

        if ($response->successful()) {
            return (array) $response->json();
        }

        // 429: der Hausschlüssel ist über seinem Minutenlimit — in einer Minute geht es wieder.
        if ($response->serverError() || $response->tooManyRequests()) {
            throw TicketingConnectionException::transient(
                __('Artwork-Tickets rejected the request.') . " (HTTP {$response->status()})"
            );
        }

        // Die Geschäftsregeln kommen als Code; die beiden, an denen eine Freigabe scheitern kann, in Worten.
        $message = match ($response->json('error.code')) {
            'HOUSE_DETAILS_MISSING' => __('Artwork-Tickets is still missing details of the house: the legal details, the payout account verified by the payment provider or the legal pages of the shop. Until they are complete, nothing can be released for sale.'),
            'HOUSE_IN_REVIEW' => __('Artwork-Tickets is still reviewing the house. Until it is approved, nothing can be released for sale.'),
            'HOUSE_DETAILS_LOCKED' => __('Artwork-Tickets keeps the legal details once they are complete. They can be changed, but not removed.'),
            default => $response->json('error.message')
                ?? __('Artwork-Tickets rejected the request.') . " (HTTP {$response->status()})",
        };

        // Validierungsfehler nennen das Feld, sonst bleibt "The request is invalid." ein Rätsel.
        if (is_array($fields = $response->json('error.fields'))) {
            $message .= ' (' . implode(', ', array_keys($fields)) . ')';
        }

        throw new TicketingConnectionException($message);
    }

    /**
     * @param 'PUT'|'POST' $method
     * @return array<string, mixed>
     */
    private function sendFile(
        string $method,
        TicketingConnection $connection,
        string $path,
        string $contents,
        string $contentType,
        string $fileName,
    ): array {
        return $this->send(fn (): Response => $this->house($connection)
            ->withHeaders(['x-file-name' => $fileName])
            ->withBody($contents, $contentType)
            ->send($method, $this->url($path)));
    }
}
