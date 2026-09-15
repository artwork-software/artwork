<?php

namespace Artwork\Modules\Ticketing\Services;

use Artwork\Modules\Ticketing\Exceptions\TicketingConnectionException;
use Artwork\Modules\Ticketing\Models\TicketingConnection;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

/**
 * HTTP zu artwork tickets. Zwei Ausweise: der Provisionierungsschlüssel der Plattform darf Häuser
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

    /** @return array<string, mixed> */
    public function get(TicketingConnection $connection, string $path): array
    {
        return $this->send(fn (): Response => $this->house($connection)->get($this->url($path)));
    }

    /**
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    public function post(TicketingConnection $connection, string $path, array $payload = []): array
    {
        return $this->send(fn (): Response => $this->house($connection)->post($this->url($path), $payload));
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
        return $this->send(fn (): Response => $this->house($connection)
            ->withHeaders(['x-file-name' => $fileName])
            ->withBody($contents, $contentType)
            ->put($this->url($path)));
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
        return Http::acceptJson()->timeout(30);
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
            throw new TicketingConnectionException(__('artwork tickets could not be reached.'), 0, $exception);
        }

        if ($response->successful()) {
            return (array) $response->json();
        }

        $message = $response->json('error.message')
            ?? __('artwork tickets rejected the request.') . " (HTTP {$response->status()})";

        // Validierungsfehler nennen das Feld, sonst bleibt "The request is invalid." ein Rätsel.
        if (is_array($fields = $response->json('error.fields'))) {
            $message .= ' (' . implode(', ', array_keys($fields)) . ')';
        }

        throw new TicketingConnectionException($message);
    }
}
