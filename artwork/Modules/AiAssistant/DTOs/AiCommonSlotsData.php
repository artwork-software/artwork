<?php

namespace Artwork\Modules\AiAssistant\DTOs;

use Illuminate\Http\Request;
use Spatie\LaravelData\Data;
use Symfony\Component\HttpFoundation\Response;

/**
 * Antwort von `POST /api/v1/ai/people/common-free-slots` (ai-scenarios-4 S14): hoechstens 10 Fenster,
 * chronologisch, in denen alle Personen (und optional der Raum) frei sind. Gruende fuer belegte
 * Zeiten werden bewusst nicht ausgegeben — keine fremden Termindetails.
 */
class AiCommonSlotsData extends Data
{
    /**
     * @param AiTimeSlotData[] $slots
     * @param AiPersonData[] $people
     */
    public function __construct(
        public array $slots,
        public array $people,
        public ?AiRefData $room,
        public int $duration_minutes,
        public string $day_start,
        public string $day_end,
    ) {
    }

    /** Eine Suche legt nichts an — 200 statt des POST-Standards 201. */
    protected function calculateResponseStatus(Request $request): int
    {
        return Response::HTTP_OK;
    }
}
