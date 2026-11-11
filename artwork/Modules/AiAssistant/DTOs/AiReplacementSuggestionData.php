<?php

namespace Artwork\Modules\AiAssistant\DTOs;

use Illuminate\Http\Request;
use Spatie\LaravelData\Data;
use Symfony\Component\HttpFoundation\Response;

/**
 * Antwort von `POST /api/v1/ai/shift-plan/replacements` (ai-scenarios-4 S9). Schreibt nichts.
 */
class AiReplacementSuggestionData extends Data
{
    /** @param AiReplacementShiftData[] $shifts */
    public function __construct(
        public AiPersonData $person,
        public array $shifts,
        public AiReplacementTotalsData $totals,
    ) {
    }

    /** Ein Vorschlag legt nichts an — 200 statt des POST-Standards 201. */
    //phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundInExtendedClass
    protected function calculateResponseStatus(Request $request): int
    {
        return Response::HTTP_OK;
    }
}
