<?php

namespace Artwork\Modules\Shift\Rules;

use Artwork\Core\Services\HelperService;
use Closure;
use Illuminate\Contracts\Validation\DataAwareRule;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

/**
 * Existiert die ISO-Kalenderwoche im angegebenen Jahr? KW 53 gibt es nur in 53-Wochen-Jahren
 * (2026 ja, 2025 nein) — Carbon würde sonst still in KW 1 des Folgejahres überrollen,
 * HelperService::getDateRangeByCalendarWeekAndYear deckelt auf die letzte KW. Beides ist für
 * neue Festschreibungen/Anfragen/Kopien falsch, deshalb hier 422 statt stiller Korrektur.
 *
 * Zwei Einsatzarten:
 *  - auf dem Wochenfeld selbst (`week_number` => new IsoWeekExists('year')): das Jahr wird als
 *    Geschwisterfeld gelesen (bei `targets.*.week` also `targets.0.year`).
 *  - auf einem Array-Element (`targets.*` => new IsoWeekExists('year', 'week')): Woche und Jahr
 *    werden aus dem Element gelesen, der Fehler hängt am Element (`targets.0`) — so bleibt die
 *    Fehleradresse für Frontend und Tests stabil.
 *
 * Typ-/Bereichsfehler (integer, min:1, max:53) melden die Standardregeln; diese Regel schweigt dann.
 */
class IsoWeekExists implements ValidationRule, DataAwareRule
{
    /** @var array<string, mixed> */
    private array $data = [];

    public function __construct(
        private readonly string $yearField = 'year',
        private readonly ?string $weekField = null
    ) {
    }

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
        if ($this->weekField !== null) {
            if (!is_array($value)) {
                return;
            }
            $week = $value[$this->weekField] ?? null;
            $year = $value[$this->yearField] ?? null;
        } else {
            $week = $value;
            $parent = Str::contains($attribute, '.') ? Str::beforeLast($attribute, '.') . '.' : '';
            $year = Arr::get($this->data, $parent . $this->yearField);
        }

        if (!is_numeric($week) || !is_numeric($year)) {
            return;
        }

        $week = (int) $week;
        $year = (int) $year;

        // Außerhalb 1–53 bzw. ohne sinnvolles Jahr greifen min/max/integer der Standardregeln.
        if ($week < 1 || $week > 53 || $year < 1) {
            return;
        }

        if (!HelperService::isoWeekExists($week, $year)) {
            $fail(__('Calendar week :week does not exist in :year.', ['week' => $week, 'year' => $year]));
        }
    }
}
