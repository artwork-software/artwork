<?php

namespace Artwork\Modules\AiAssistant\Support;

use Artwork\Modules\AiAssistant\Contracts\BatchRecorder;
use Artwork\Modules\User\Models\User;
use Illuminate\Support\Facades\Log;

/**
 * Platzhalter bis `core-undo` (W3): es wird kein Batch angelegt, die Ausfuehrung ist also nicht
 * rueckgaengig zu machen. Die Warnung im Log ist Absicht — sie faellt auf, falls die Ausfuehrung
 * scharf geschaltet wird, bevor W3 steht.
 */
final class NullBatchRecorder implements BatchRecorder
{
    /**
     * @param list<array{
     *     model_type: string,
     *     model_id: int,
     *     action: string,
     *     before_state: array<string, mixed>|null
     * }> $items
     */
    public function record(User $user, string $tool, array $items): ?RecordedBatch
    {
        Log::warning('KI-Assistent: Aktion ohne Batch ausgefuehrt — Undo ist bis core-undo nicht moeglich.', [
            'user_id' => $user->id,
            'tool' => $tool,
            'items' => count($items),
        ]);

        return null;
    }
}
