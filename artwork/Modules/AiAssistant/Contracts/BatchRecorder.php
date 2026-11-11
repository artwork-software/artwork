<?php

namespace Artwork\Modules\AiAssistant\Contracts;

use Artwork\Modules\AiAssistant\Support\RecordedBatch;
use Artwork\Modules\User\Models\User;

/**
 * Aufzeichnung einer ausgefuehrten Aktion als Batch (Konzept W3 §1).
 *
 * In diesem Paket (W1) gibt es nur `NullBatchRecorder`; `core-undo` (W3) liefert die
 * Implementierung mit `ai_action_batches`/`ai_action_batch_items`. Die Gespraechs-ID holt sich die
 * Implementierung wie der Herkunfts-Listener aus dem Header `X-Ai-Conversation` — sie steht
 * deshalb nicht in der Signatur.
 */
interface BatchRecorder
{
    /**
     * @param list<array{
     *     model_type: string,
     *     model_id: int,
     *     action: string,
     *     before_state: array<string, mixed>|null
     * }> $items
     * @return RecordedBatch|null null, solange es keine Aufzeichnung gibt
     */
    public function record(User $user, string $tool, array $items): ?RecordedBatch;
}
