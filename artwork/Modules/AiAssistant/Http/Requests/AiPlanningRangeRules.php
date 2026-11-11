<?php

namespace Artwork\Modules\AiAssistant\Http\Requests;

use Carbon\CarbonImmutable;
use Illuminate\Contracts\Validation\Validator;

/**
 * Zeitraum der Planungs-Endpunkte (ai-shift-planning, ai-scenarios-3): `from`/`to` als `YYYY-MM-DD`
 * oder Datum-Uhrzeit (wie `AiTimeRangeRequest`), Pflicht, `to >= from`, hoechstens
 * 92 Tage — enger als die 180 Tage der anderen Lese-Endpunkte, weil hier je Tag gerechnet wird.
 */
trait AiPlanningRangeRules
{
    public static int $maxPlanningDays = 92;

    /**
     * @return array<string, array<int, string>>
     */
    protected function planningRangeRules(): array
    {
        return [
            'from' => ['required', 'date'],
            'to' => ['required', 'date', 'after_or_equal:from'],
        ];
    }

    protected function validatePlanningRange(Validator $validator): void
    {
        $from = $this->input('from');
        $to = $this->input('to');

        if (!is_string($from) || !is_string($to) || $validator->errors()->hasAny(['from', 'to'])) {
            return;
        }

        $max = self::$maxPlanningDays;

        if (CarbonImmutable::parse($from)->diffInDays(CarbonImmutable::parse($to)) > $max) {
            $validator->errors()->add('to', "Der Zeitraum darf hoechstens {$max} Tage umfassen.");
        }
    }

    /**
     * `YYYY-MM-DD` oder Datum-Uhrzeit (RFC 3339); gerechnet wird mit dem Kalendertag in Haus-Zeitzone.
     */
    public function from(): CarbonImmutable
    {
        return self::houseDay((string) $this->validated('from'));
    }

    public function to(): CarbonImmutable
    {
        return self::houseDay((string) $this->validated('to'));
    }

    private static function houseDay(string $value): CarbonImmutable
    {
        $timezone = (string) config('app.timezone');

        return CarbonImmutable::parse($value, $timezone)->setTimezone($timezone)->startOfDay();
    }
}
