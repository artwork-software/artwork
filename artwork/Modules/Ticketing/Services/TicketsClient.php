<?php

namespace Artwork\Modules\Ticketing\Services;

use Artwork\Modules\Ticketing\Exceptions\TicketingConnectionException;
use Artwork\Modules\Ticketing\Models\TicketingConnection;
use Artwork\Modules\User\Models\User;
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
     * Wie tickets eine artwork-Person nennt, die dort etwas tut — einlässt, eine Karte korrigiert.
     *
     * @return array{email: string, name: string}
     */
    public function actor(User $user): array
    {
        return ['email' => $user->email, 'name' => $user->full_name];
    }

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

        // Die Geschäftsregeln kommen als Code; die, an denen Freigabe, Karte oder Einlass scheitern können, in Worten.
        $code = $response->json('error.code');
        $message = match ($code) {
            'HOUSE_DETAILS_MISSING' => __(
                'Artwork-Tickets is still missing details of the house: the legal details, the payout account '
                . 'verified by the payment provider or the legal pages of the shop. Until they are complete, '
                . 'nothing can be released for sale.'
            ),
            'HOUSE_IN_REVIEW' => __('Artwork-Tickets is still reviewing the house. Until it is approved, nothing can be released for sale.'),
            'PASS_TYPE_SOLD' => __('Passes of this kind have been sold already: uses, value, limits and term stay as they are, productions can only be added.'),
            'PASS_IN_USE' => __('Passes of this kind have been sold already, so it cannot be deleted. Archive it to end the sale.'),
            'NAME_TAKEN' => __('Artwork-Tickets already has a pass or group with this name.'),
            'GROUP_IN_USE' => __('Sold passes or redeemed codes count on this group: it can only grow.'),
            'ITEM_UNAVAILABLE' => __('No place is left in this price class tonight.'),
            'SEAT_SELECTION_REQUIRED' => __('This date has a seating plan; sell the seat in Artwork-Tickets.'),
            'ROOM_CHANGE_AFTER_SALE' => self::roomStaysMessage(),
            'PASS_NOT_ELIGIBLE' => __('This pass cannot be used for this date.'),
            'PASS_EXPIRED' => __('This pass is no longer valid.'),
            'PASS_PAUSED' => __('This pass is paused with its subscription.'),
            'SUBSCRIPTION_NOT_CANCELLABLE' => __('This subscription has ended already or has not been paid yet.'),
            'SUBSCRIPTION_NOT_PAUSABLE' => __('This subscription cannot be paused right now: it is paused already, has ended or its last payment is open.'),
            'PASS_EXHAUSTED' => __('This pass has no uses left.'),
            'PASS_LIMIT_REACHED' => __('This pass has reached its limit for this production.'),
            'PASS_PERIOD_LIMIT_REACHED' => __('This pass has reached its limit for this period.'),
            'HOLD_EXPIRED' => __('The place could not be held. Please try again.'),
            'ADMISSION_RUNNING' => __('This admission has not finished yet. Check the guest list before trying again.'),
            'HOUSE_LIVE' => __('This ticket house already sells for real; test mode has ended.'),
            'HOUSE_DETAILS_LOCKED' => __('Artwork-Tickets keeps the legal details once they are complete. They can be changed, but not removed.'),
            'PASS_NOT_ENOUGH_USES' => __('Fewer uses are left on this pass than should be taken off.'),
            'SUBSCRIPTION_NOT_PAUSED' => __('This subscription is not paused.'),
            'SUBSCRIPTION_NOT_CANCELLED' => __('This subscription has no cancellation to withdraw.'),
            'PAYMENT_ABORTED' => __('The payment provider did not complete the change. Please try again later.'),
            'ORDER_UNFULFILLABLE' => __('Artwork-Tickets could not complete the order.'),
            'CAPACITY_BELOW_SOLD' => __('The capacity cannot go below the tickets sold already.'),
            'ALREADY_SOLD' => __('Tickets have been sold for this already, so it stays as it is.'),
            'VENUE_IN_USE' => __('Dates in Artwork-Tickets still use this room.'),
            'ZONE_IN_USE' => __('Dates in Artwork-Tickets still use this price class.'),
            'PLAN_LOCKED' => __('The seating plan of this room sets its price classes.'),
            'SEAT_TAKEN' => __('This seat has just been taken.'),
            'SEATS_IN_USE' => __('Tickets have been sold for these seats already.'),
            'REDUCTION_NOT_AVAILABLE' => __('This reduction does not apply here.'),
            'ALREADY_MEMBER' => __('This person is in the ticket house already.'),
            'ALREADY_IN_HOUSE' => __('This account belongs to a ticket house already.'),
            'LAST_OWNER' => __('A ticket house needs at least one owner.'),
            'VALIDATION_FAILED' => __('Artwork-Tickets did not accept some of the entries.'),
            'UNAUTHORIZED', 'FORBIDDEN' => __('Artwork-Tickets did not accept the key of this connection.'),
            'NOT_FOUND' => __('Artwork-Tickets no longer has this entry. Refresh the view and try again.'),
            'UNEXPECTED' => __('Something went wrong in Artwork-Tickets. Please try again later.'),
            // Ohne eigene Übersetzung zählt der Satz, den tickets mitschickt, nie aber der nackte Code.
            default => $this->sentence($response)
                ?? __('Artwork-Tickets rejected the request.') . " (HTTP {$response->status()})",
        };

        // Validierungsfehler nennen das Feld, sonst bleibt "The request is invalid." ein Rätsel.
        if (is_array($fields = $response->json('error.fields'))) {
            $message .= ' (' . implode(', ', array_keys($fields)) . ')';
        }

        throw new TicketingConnectionException($message, ticketsCode: is_string($code) ? $code : null);
    }

    /** Die Regel prüft auch core, bevor es tickets fragt; beide sagen es mit denselben Worten. */
    public static function roomStaysMessage(): string
    {
        return __(
            'Tickets for this date are sold and one of the two rooms has a seating plan, so the date stays in its room: sold seats belong to its chairs.'
        );
    }

    private function sentence(Response $response): ?string
    {
        $message = $response->json('error.message');

        return is_string($message) && $message !== $response->json('error.code') ? $message : null;
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
