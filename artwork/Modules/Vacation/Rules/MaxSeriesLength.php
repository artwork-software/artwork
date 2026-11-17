<?php

namespace Artwork\Modules\Vacation\Rules;

use Carbon\Carbon;
use Closure;
use Illuminate\Contracts\Validation\DataAwareRule;
use Illuminate\Contracts\Validation\ValidationRule;
use Throwable;

/**
 * Serien von Abwesenheiten und Verfügbarkeiten enden spätestens fünf Jahre nach dem Starttag. Ohne Grenze
 * legte eine tägliche Serie bis z. B. 2100 zehntausende Einträge in einem Request an.
 */
class MaxSeriesLength implements DataAwareRule, ValidationRule
{
    public const MAX_YEARS = 5;

    /** @var array<string, mixed> */
    private array $data = [];

    /**
     * @param array<string, mixed> $data
     */
    public function setData(array $data): static
    {
        $this->data = $data;

        return $this;
    }

    /**
     * @param Closure(string, ?string=): \Illuminate\Translation\PotentiallyTranslatedString $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!is_string($value) || $value === '' || !is_string($this->data['date'] ?? null)) {
            return;
        }

        try {
            $latestEnd = Carbon::parse($this->data['date'])->addYears(self::MAX_YEARS);
            $seriesEnd = Carbon::parse($value);
        } catch (Throwable) {
            // ungültige Daten meldet bereits die date-Regel
            return;
        }

        if ($seriesEnd->gt($latestEnd)) {
            $fail(__('A series may run for at most :years years.', ['years' => self::MAX_YEARS]));
        }
    }
}
