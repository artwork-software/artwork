<?php

namespace Artwork\Modules\AiAssistant\Http\Requests;

use Carbon\CarbonImmutable;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Gemeinsame Regeln der zeitbezogenen Lese-Endpunkte (Konzept B1 §4).
 *
 * Pflicht-Zeitraum und Hoechstlaenge stehen hier an einer Stelle: kein Endpunkt liefert „alles",
 * und kein Modell kann sich mit einem sehr weiten Zeitraum an der Obergrenze vorbeischummeln.
 */
abstract class AiTimeRangeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, string>>
     */
    protected function rangeRules(): array
    {
        return [
            'from' => ['required', 'date'],
            'to' => ['required', 'date', 'after_or_equal:from'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $from = $this->query('from');
            $to = $this->query('to');

            if (!is_string($from) || !is_string($to)) {
                return;
            }

            $maxDays = (int) config('ai.max_range_days');

            if (CarbonImmutable::parse($from)->diffInDays(CarbonImmutable::parse($to)) > $maxDays) {
                $validator->errors()->add('to', "Der Zeitraum darf hoechstens {$maxDays} Tage umfassen.");
            }
        });
    }

    public function from(): CarbonImmutable
    {
        return CarbonImmutable::parse((string) $this->validated('from'))->startOfDay();
    }

    public function to(): CarbonImmutable
    {
        return CarbonImmutable::parse((string) $this->validated('to'))->endOfDay();
    }

    /**
     * @return list<int>
     */
    public function roomIds(): array
    {
        /** @var list<int|string> $ids */
        $ids = (array) $this->validated('room_ids', []);

        return array_values(array_map('intval', $ids));
    }
}
