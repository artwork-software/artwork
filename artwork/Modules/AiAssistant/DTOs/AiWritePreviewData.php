<?php

namespace Artwork\Modules\AiAssistant\DTOs;

use Illuminate\Http\Request;
use Spatie\LaravelData\Data;
use Symfony\Component\HttpFoundation\Response;

/**
 * Antwort eines Schreib-Endpunkts mit `dry_run=true` (Konzept W1 §2).
 *
 * Die Vorschau entsteht, indem der **echte** Bulk-Service der Oberflaeche in einer Transaktion
 * laeuft, die anschliessend zurueckgerollt wird — sie zeigt damit exakt das Ergebnis der
 * spaeteren Ausfuehrung und nicht eine nachgebaute Schaetzung.
 */
class AiWritePreviewData extends Data
{
    /** @param AiWriteItemData[] $items */
    public function __construct(
        public bool $dry_run,
        public string $tool,
        public array $items,
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
