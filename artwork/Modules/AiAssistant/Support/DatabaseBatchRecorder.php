<?php

namespace Artwork\Modules\AiAssistant\Support;

use Artwork\Modules\AiAssistant\Contracts\BatchRecorder;
use Artwork\Modules\AiAssistant\Services\AiActionBatchService;
use Artwork\Modules\User\Models\User;

/**
 * Schreibt den Batch einer ausgefuehrten Aktion (core-undo; Konzept W3 §1).
 *
 * Ersetzt `NullBatchRecorder` aus W1. Laeuft in der Transaktion der Ausfuehrung — entweder stehen
 * Aenderung und Batch zusammen in der Datenbank oder keines von beiden.
 */
final class DatabaseBatchRecorder implements BatchRecorder
{
    public function __construct(private readonly AiActionBatchService $batches)
    {
    }

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
        return $this->batches->record($user, $tool, $items);
    }
}
