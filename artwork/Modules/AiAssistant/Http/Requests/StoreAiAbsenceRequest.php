<?php

namespace Artwork\Modules\AiAssistant\Http\Requests;

use Artwork\Modules\AiAssistant\Services\AiAbsenceWriteService;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Validation\Rule;

/**
 * POST /api/v1/ai/absences (ai-scenarios-4 S11; Werkzeug `create_absence`).
 *
 * `days` ist entweder eine Liste `["Y-m-d", …]` oder ein Bereich `{from_day, to_day}`;
 * `full_day` ist `true` oder `{start_time, end_time}` (H:i). Hoechstens 92 Tage.
 */
class StoreAiAbsenceRequest extends AiWriteRequest
{
    public const MAX_DAYS = 92;

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return array_merge($this->writeRules(), SuggestAiShiftPlanRequest::personRules(), [
            // `@var` uebersteuert das Schema im Export (beide Formen; Muster `pending_action.args`).
            /** @var list<string>|array{from_day: string, to_day: string} */
            'days' => ['required', 'array'],
            'type' => ['required', Rule::in(AiAbsenceWriteService::TYPES)],
            /** @var true|array{start_time: string, end_time: string} */
            'full_day' => ['required'],
            'comment' => ['nullable', 'string', 'max:100'],
        ]);
    }

    public function withValidator(Validator $validator): void
    {
        parent::withValidator($validator);

        $validator->after(function (Validator $validator): void {
            if ($validator->errors()->hasAny(['days', 'full_day'])) {
                return;
            }

            $days = $this->rawDays();
            if ($days === null) {
                $validator->errors()->add('days', 'days ist eine Liste von Tagen (Y-m-d) oder {from_day, to_day}.');
            } elseif ($days === [] || count($days) > self::MAX_DAYS) {
                $validator->errors()->add('days', 'Es sind 1 bis ' . self::MAX_DAYS . ' Tage moeglich.');
            }

            $fullDay = $this->input('full_day');
            if ($fullDay === true) {
                return;
            }

            if (
                !is_array($fullDay)
                || !self::isTime($fullDay['start_time'] ?? null)
                || !self::isTime($fullDay['end_time'] ?? null)
                || (string) $fullDay['end_time'] <= (string) $fullDay['start_time']
            ) {
                $validator->errors()->add(
                    'full_day',
                    'full_day ist true oder {start_time, end_time} (H:i, Ende nach Beginn).'
                );
            }
        });
    }

    /**
     * @return list<string>
     */
    public function days(): array
    {
        return $this->rawDays() ?? [];
    }

    /**
     * @return array{start_time: string, end_time: string}|null null = ganztaegig
     */
    public function times(): ?array
    {
        $fullDay = $this->input('full_day');

        return is_array($fullDay)
            ? ['start_time' => (string) $fullDay['start_time'], 'end_time' => (string) $fullDay['end_time']]
            : null;
    }

    /**
     * @return list<string>|null
     */
    private function rawDays(): ?array
    {
        $days = $this->input('days');

        if (!is_array($days)) {
            return null;
        }

        if (array_is_list($days)) {
            foreach ($days as $day) {
                if (!self::isDay($day)) {
                    return null;
                }
            }
            $days = array_values(array_unique($days));
            sort($days);

            return $days;
        }

        $from = $days['from_day'] ?? null;
        $to = $days['to_day'] ?? null;
        if (!self::isDay($from) || !self::isDay($to) || $to < $from) {
            return null;
        }

        $list = [];
        $day = CarbonImmutable::parse($from);
        $last = CarbonImmutable::parse($to);
        while ($day->lessThanOrEqualTo($last) && count($list) <= self::MAX_DAYS) {
            $list[] = $day->toDateString();
            $day = $day->addDay();
        }

        return $list;
    }

    private static function isDay(mixed $value): bool
    {
        return is_string($value)
            && preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) === 1
            && CarbonImmutable::createFromFormat('Y-m-d', $value)?->toDateString() === $value;
    }

    private static function isTime(mixed $value): bool
    {
        return is_string($value) && preg_match('/^([01]\d|2[0-3]):[0-5]\d$/', $value) === 1;
    }
}
