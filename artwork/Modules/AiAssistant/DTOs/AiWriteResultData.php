<?php

namespace Artwork\Modules\AiAssistant\DTOs;

use Illuminate\Http\Request;
use Spatie\LaravelData\Data;
use Symfony\Component\HttpFoundation\Response;

/**
 * Antwort eines Schreib-Endpunkts nach der Ausfuehrung (Konzept W1 §2, W3 §3).
 *
 * `batch_id` bleibt leer, solange `core-undo` den BatchRecorder nicht liefert.
 */
class AiWriteResultData extends Data
{
    /**
     * @param int[] $ids
     * @param AiWriteItemData[] $items
     * @param AiWriteLinkData[] $links
     */
    public function __construct(
        public bool $dry_run,
        public string $tool,
        public ?string $batch_id,
        public ?string $undo_until,
        public array $ids,
        public array $items,
        public array $links,
        public AiWriteSummaryData $summary,
    ) {
    }

    /**
     * `spatie/laravel-data` antwortet auf POST standardmaessig mit 201. Das passt hier nicht: der
     * POST legt bei `dry_run=true` nichts an, und die drei Endpunkte sollen dieselbe Form haben.
     */
    //phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundInExtendedClass
    protected function calculateResponseStatus(Request $request): int
    {
        return Response::HTTP_OK;
    }
}
