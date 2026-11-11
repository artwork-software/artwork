<?php

namespace Artwork\Modules\AiAssistant\DTOs;

use Illuminate\Http\Request;
use Spatie\LaravelData\Data;
use Symfony\Component\HttpFoundation\Response;

/**
 * Antwort von `POST /api/v1/ai/shift-plan/staff-event` (ai-scenarios-3 S3). Schreibt nichts.
 */
class AiEventStaffingData extends Data
{
    /**
     * @param AiStaffingPickData[] $picks
     * @param AiStaffingUnfilledData[] $unfilled
     */
    public function __construct(
        public AiStaffingEventData $event,
        public AiRefData $craft,
        public array $picks,
        public array $unfilled,
        public AiStaffingTotalsData $totals,
    ) {
    }

    /** Ein Vorschlag legt nichts an — 200 statt des POST-Standards 201. */
    //phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundInExtendedClass
    protected function calculateResponseStatus(Request $request): int
    {
        return Response::HTTP_OK;
    }
}
