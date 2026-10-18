<?php

namespace Artwork\Modules\Event\Http\Requests\Api;

use Artwork\Core\Api\Requests\MachineApiRequest;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Illuminate\Validation\Validator;

/**
 * Parameter von GET /api/v1/events.
 */
class EventApiIndexRequest extends MachineApiRequest
{
    public const MAX_RANGE_YEARS = 3;

    public const DEFAULT_PER_PAGE = 500;

    public const MAX_PER_PAGE = 1000;

    /** Obergrenze je Filterliste; jede ID wird einzeln gegen die Datenbank geprüft. */
    public const MAX_FILTER_IDS = 500;

    protected function prepareForValidation(): void
    {
        $this->explodeCommaSeparated(['rooms', 'areas']);

        // Ein unkodiertes „+“ im Query-String kommt als Leerzeichen an (…T09:15:00 02:00). Das ist
        // der häufigste Fehler beim Aufruf mit Zeitzone, deshalb wird er hier repariert.
        $updatedSince = $this->query('updated_since');
        if (is_string($updatedSince)) {
            $this->merge(['updated_since' => preg_replace('/ (\d{2}:?\d{2})$/', '+$1', trim($updatedSince))]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'from' => ['required', 'date_format:Y-m-d'],
            'to' => ['required', 'date_format:Y-m-d', 'after_or_equal:from'],
            'rooms' => ['sometimes', 'array', 'max:' . self::MAX_FILTER_IDS],
            'rooms.*' => $this->existingIdRules('rooms', 'rooms', self::MAX_FILTER_IDS),
            'areas' => ['sometimes', 'array', 'max:' . self::MAX_FILTER_IDS],
            'areas.*' => $this->existingIdRules('areas', 'areas', self::MAX_FILTER_IDS),
            'updated_since' => ['sometimes', 'date'],
            'page' => ['sometimes', 'integer', 'min:1'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:' . self::MAX_PER_PAGE],
        ];
    }

    /**
     * @return array<int, callable>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->hasAny(['from', 'to'])) {
                    return;
                }

                if ($this->rangeEnd()->gt($this->rangeStart()->addYears(self::MAX_RANGE_YEARS))) {
                    $validator->errors()->add(
                        'to',
                        'The period between from and to must not exceed ' . self::MAX_RANGE_YEARS . ' years.'
                    );
                }
            },
        ];
    }

    /**
     * Beginn des abgefragten Zeitraums: 00:00 Uhr am Tag „from“ in der Zeitzone der Instanz.
     */
    public function rangeStart(): CarbonImmutable
    {
        return CarbonImmutable::createFromFormat('Y-m-d', $this->string('from')->toString())->startOfDay();
    }

    /**
     * Ende des abgefragten Zeitraums (ausschließlich): 00:00 Uhr am Tag nach „to“, weil „to“ den
     * letzten Tag einschließt.
     */
    public function rangeEnd(): CarbonImmutable
    {
        return CarbonImmutable::createFromFormat('Y-m-d', $this->string('to')->toString())
            ->startOfDay()
            ->addDay();
    }

    /**
     * @return list<int>
     */
    public function roomIds(): array
    {
        return array_map('intval', $this->validated('rooms', []));
    }

    /**
     * @return list<int>
     */
    public function areaIds(): array
    {
        return array_map('intval', $this->validated('areas', []));
    }

    /**
     * Zeitpunkt aus updated_since, umgerechnet in die Zeitzone der Instanz, in der die Datenbank
     * ihre Zeitstempel speichert.
     */
    public function updatedSince(): ?Carbon
    {
        $updatedSince = $this->validated('updated_since');

        return $updatedSince === null
            ? null
            : Carbon::parse($updatedSince)->setTimezone(config('app.timezone'));
    }

    public function perPage(): int
    {
        return (int) $this->validated('per_page', self::DEFAULT_PER_PAGE);
    }
}
