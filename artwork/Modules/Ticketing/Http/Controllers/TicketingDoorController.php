<?php

namespace Artwork\Modules\Ticketing\Http\Controllers;

use App\Http\Controllers\Controller;
use Artwork\Modules\Event\Models\Event;
use Artwork\Modules\Ticketing\Exceptions\TicketingConnectionException;
use Artwork\Modules\Ticketing\Services\TicketingDoorService;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Der Einlass in den Ticketdetails eines Termins: einchecken, zurücknehmen und Karteninhaber einlassen.
 * Darf, wer an die Tür darf und das Projekt sieht (TicketingDoorService::mayAdmit).
 */
class TicketingDoorController extends Controller
{
    public function __construct(private readonly TicketingDoorService $door)
    {
    }

    public function checkIn(Event $event, Request $request): JsonResponse
    {
        $this->authorizeDoor($event, $request);
        $ticketId = $request->validate(['ticket_id' => ['required', 'uuid']])['ticket_id'];

        return $this->respond(fn (): array => $this->door->checkIn($event, ['ticketId' => $ticketId], $request->user()));
    }

    public function revert(Event $event, Request $request): JsonResponse
    {
        $this->authorizeDoor($event, $request);
        $ticketId = $request->validate(['ticket_id' => ['required', 'uuid']])['ticket_id'];

        return $this->respond(function () use ($event, $ticketId, $request): array {
            $this->door->revert($event, $ticketId, $request->user());

            return ['reverted' => true];
        });
    }

    public function passes(Event $event, Request $request): JsonResponse
    {
        $this->authorizeDoor($event, $request);
        $query = $request->validate(['q' => ['nullable', 'string', 'max:100']])['q'] ?? '';

        return $this->respond(fn (): array => $this->door->passes($event, ['q' => $query]));
    }

    public function admit(Event $event, Request $request): JsonResponse
    {
        $this->authorizeDoor($event, $request);
        $validated = $request->validate([
            'pass_id' => ['required', 'uuid'],
            'price_category_id' => ['required', 'uuid'],
        ]);

        return $this->respond(fn (): array => $this->door->admit(
            $event,
            $validated['pass_id'],
            $validated['price_category_id'],
            $request->user(),
        ));
    }

    /** @param Closure(): array<string, mixed> $call */
    private function respond(Closure $call): JsonResponse
    {
        try {
            return response()->json($call());
        } catch (TicketingConnectionException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }
    }

    private function authorizeDoor(Event $event, Request $request): void
    {
        abort_unless($this->door->mayAdmit($request->user(), $event), 403);
    }
}
