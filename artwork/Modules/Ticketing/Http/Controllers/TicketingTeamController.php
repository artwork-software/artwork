<?php

namespace Artwork\Modules\Ticketing\Http\Controllers;

use App\Http\Controllers\Controller;
use Artwork\Modules\Ticketing\Exceptions\TicketingConnectionException;
use Artwork\Modules\Ticketing\Http\Requests\InviteTicketingTeamRequest;
use Artwork\Modules\Ticketing\Http\Requests\TicketingDraftRules;
use Artwork\Modules\Ticketing\Services\TicketingConnectionService;
use Artwork\Modules\Ticketing\Services\TicketingTeamService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

/** Einstellungen → artwork tickets → Team: Leute von hier ins Ticket-Haus einladen. */
class TicketingTeamController extends Controller
{
    public function __construct(
        private readonly TicketingConnectionService $connections,
        private readonly TicketingTeamService $team,
    ) {
    }

    public function index(): Response
    {
        $connection = $this->connections->current();
        $people = [];
        $ticketsError = null;

        if ($connection) {
            try {
                $people = $this->team->people($connection);
            } catch (TicketingConnectionException $exception) {
                $ticketsError = $exception->getMessage();
            }
        }

        return Inertia::render('Settings/Tickets/Team', [
            'connection' => [
                'connected' => $connection !== null,
            ],
            'people' => $people,
            'presets' => TicketingDraftRules::TEAM_PRESETS,
            'ticketsError' => $ticketsError,
        ]);
    }

    public function invite(InviteTicketingTeamRequest $request): RedirectResponse
    {
        $connection = $this->connections->current();

        if (!$connection) {
            return back()->with('error', __('This installation is not connected to artwork tickets.'));
        }

        try {
            $results = $this->team->invite(
                $connection,
                $request->user(),
                array_map('intval', $request->validated('user_ids')),
                $request->validated('preset'),
            );
        } catch (TicketingConnectionException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        $invited = count(array_filter($results, static fn (array $result): bool => $result['status'] === 'invited'));

        return back()->with('success', trans_choice('{1} One invitation is on its way.|[2,*] :count invitations are on their way.', $invited, ['count' => $invited]));
    }
}
