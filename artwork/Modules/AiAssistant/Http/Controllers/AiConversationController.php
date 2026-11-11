<?php

namespace Artwork\Modules\AiAssistant\Http\Controllers;

use App\Http\Controllers\Controller;
use Artwork\Modules\AiAssistant\DTOs\AiConversationListData;
use Artwork\Modules\AiAssistant\Models\AiConversation;
use Artwork\Modules\AiAssistant\Policies\AiConversationPolicy;
use Artwork\Modules\AiAssistant\Services\AiConversationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Gespraechsliste und Loeschen fuer die KI-Bar (core-chat-historie; Konzept C3 §5, §7).
 *
 * Die Eigentuemerpruefung laeuft ueber AiConversationPolicy — direkt aufgerufen, nie ueber
 * $this->authorize(). Gate::before gibt Admins sonst jede Gate-Abfrage frei, und ein Gespraech
 * gehoert der Person, die es gefuehrt hat.
 */
class AiConversationController extends Controller
{
    private const PER_PAGE = 20;

    public function __construct(
        private readonly AiConversationService $service,
        private readonly AiConversationPolicy $policy,
    ) {
    }

    public function index(Request $request): AiConversationListData
    {
        $conversations = AiConversation::query()
            ->where('user_id', $request->user()->id)
            ->orderByDesc('last_turn_at')
            ->orderByDesc('created_at')
            ->paginate(self::PER_PAGE);

        return AiConversationListData::fromPaginator($conversations);
    }

    public function destroy(Request $request, string $conversation): JsonResponse
    {
        $model = $this->service->findForUserOrFail($request->user(), $conversation);

        // Doppelt gehalten: Die Abfrage ist bereits auf den User eingeschraenkt (404 statt 403),
        // die Policy haelt die Regel an einer benennbaren Stelle fest.
        abort_unless($this->policy->delete($request->user(), $model), Response::HTTP_NOT_FOUND);

        $title = $model->title;
        $model->delete();

        // Ohne Nachrichteninhalt — der gehoert nicht ins Activity-Log (Konzept C3 §5).
        activity()
            ->causedBy($request->user())
            ->withProperties(['title' => $title])
            ->log('ai_conversation_deleted');

        return response()->json(status: Response::HTTP_NO_CONTENT);
    }
}
