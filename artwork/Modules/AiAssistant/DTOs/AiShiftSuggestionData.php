<?php

namespace Artwork\Modules\AiAssistant\DTOs;

use Illuminate\Http\Request;
use Spatie\LaravelData\Data;
use Symfony\Component\HttpFoundation\Response;

/**
 * Antwort von `POST /api/v1/ai/shift-plan/suggest` (ai-shift-planning). Schreibt nichts.
 */
class AiShiftSuggestionData extends Data
{
    /**
     * @param AiShiftPickData[] $picked
     * @param AiShiftSkipData[] $skipped
     */
    public function __construct(
        public AiPersonData $person,
        public array $picked,
        public array $skipped,
        public AiShiftTotalsData $totals,
        public AiShiftLimitsData $limits_applied,
    ) {
    }

    /** Ein Vorschlag legt nichts an — 200 statt des POST-Standards 201. */
    //phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundInExtendedClass
    protected function calculateResponseStatus(Request $request): int
    {
        return Response::HTTP_OK;
    }
}
