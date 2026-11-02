<?php

namespace Artwork\Modules\AppApi\Http\Controllers;

use App\Http\Controllers\Controller;
use Artwork\Modules\AppApi\Http\Requests\AppCheckInRequest;
use Artwork\Modules\AppApi\Http\Requests\AppDateRangeRequest;
use Artwork\Modules\AppApi\Http\Requests\AppDoorPassesRequest;
use Artwork\Modules\AppApi\Http\Requests\AppPassAdmissionRequest;
use Artwork\Modules\AppApi\Http\Requests\AppRevertCheckInRequest;
use Artwork\Modules\AppApi\Services\AppTicketingService;
use Artwork\Modules\Event\Models\Event;
use Artwork\Modules\Ticketing\Exceptions\TicketingConnectionException;
use Artwork\Modules\Ticketing\Services\TicketingDoorService;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * The door in the app, run through artwork like the ticket details on the web: whoever may run
 * the door there (TicketingDoorService::mayAdmit) may here. A refusal from tickets comes back as
 * 422 with the message in words and tickets' code.
 */
class AppTicketingController extends Controller
{
    public function __construct(
        private readonly AppTicketingService $ticketing,
        private readonly TicketingDoorService $door,
    ) {
    }

    public function dates(AppDateRangeRequest $request): JsonResponse
    {
        abort_unless($this->door->mayRunDoor($request->user()), 403);

        return $this->respond(fn (): array => [
            'start' => $request->startDate()->format('Y-m-d'),
            'end' => $request->endDate()->format('Y-m-d'),
            'dates' => $this->ticketing->dates($request->user(), $request->startDate(), $request->endDate()),
        ]);
    }

    public function show(Event $event, Request $request): JsonResponse
    {
        $this->authorizeDoor($event, $request);

        return $this->respond(function () use ($event): array {
            $date = $this->ticketing->show($event);
            abort_if($date === null, 404);

            return ['date' => $date];
        });
    }

    public function checkIn(Event $event, AppCheckInRequest $request): JsonResponse
    {
        $this->authorizeDoor($event, $request);

        return $this->respond(fn (): array => $this->ticketing->checkIn(
            $event,
            $request->attempt(),
            $request->user(),
            (string) $request->validated('scan_id'),
        ));
    }

    public function revert(Event $event, AppRevertCheckInRequest $request): JsonResponse
    {
        $this->authorizeDoor($event, $request);

        return $this->respond(function () use ($event, $request): array {
            $this->ticketing->revert($event, (string) $request->validated('ticket_id'), $request->user());

            return ['reverted' => true];
        });
    }

    public function passes(Event $event, AppDoorPassesRequest $request): JsonResponse
    {
        $this->authorizeDoor($event, $request);

        return $this->respond(fn (): array => $this->ticketing->passes($event, $request->lookup()));
    }

    public function admit(Event $event, AppPassAdmissionRequest $request): JsonResponse
    {
        $this->authorizeDoor($event, $request);

        return $this->respond(fn (): array => $this->ticketing->admit(
            $event,
            (string) $request->validated('admission_id'),
            (string) $request->validated('pass_id'),
            (string) $request->validated('price_category_id'),
            $request->user(),
        ));
    }

    /** @param Closure(): array<string, mixed> $call */
    private function respond(Closure $call): JsonResponse
    {
        try {
            return response()->json($call());
        } catch (TicketingConnectionException $exception) {
            return response()->json(array_filter([
                'message' => $exception->getMessage(),
                'code' => $exception->ticketsCode(),
            ], fn (?string $value): bool => $value !== null), 422);
        }
    }

    private function authorizeDoor(Event $event, Request $request): void
    {
        abort_unless($this->door->mayAdmit($request->user(), $event), 403);
    }
}
