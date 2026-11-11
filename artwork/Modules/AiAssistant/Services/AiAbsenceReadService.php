<?php

namespace Artwork\Modules\AiAssistant\Services;

use Artwork\Modules\AiAssistant\DTOs\AiAbsencePeriodData;
use Artwork\Modules\AiAssistant\DTOs\AiPersonAbsencesData;
use Artwork\Modules\AiAssistant\DTOs\AiPersonAbsencesListData;
use Artwork\Modules\Freelancer\Models\Freelancer;
use Artwork\Modules\ServiceProvider\Models\ServiceProvider;
use Artwork\Modules\User\Models\User;
use Artwork\Modules\Vacation\Enums\Vacation as VacationType;
use Artwork\Modules\Vacation\Models\Vacation;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

/**
 * Abwesenheiten (ai-scenarios-3 S4): Urlaub (`OFF_WORK`) und Nicht-verfuegbar (`NOT_AVAILABLE`) von
 * Nutzer:innen, Freelancern und Dienstleistern. Aufeinanderfolgende ganztaegige Tage gleicher Art
 * werden zu einem Zeitraum zusammengefasst (wie `AiPeopleService::absences()`).
 */
class AiAbsenceReadService
{
    public const TYPE_VACATION = 'vacation';

    public const TYPE_NOT_AVAILABLE = 'not_available';

    private const CLASSES = [User::class, Freelancer::class, ServiceProvider::class];

    public function __construct(private readonly AiPeopleService $people)
    {
    }

    public function list(
        CarbonImmutable $from,
        CarbonImmutable $to,
        ?int $craftId,
        ?string $search,
        ?int $limit
    ): AiPersonAbsencesListData {
        $max = (int) config('ai.list_limit');
        $limit = $limit === null || $limit < 1 ? $max : min($limit, $max);
        $entries = $this->entries($from, $to, $craftId, $search);

        return new AiPersonAbsencesListData(
            data: array_slice($entries, 0, $limit),
            total: count($entries),
            limit: $limit,
            truncated: count($entries) > $limit,
        );
    }

    /**
     * @return list<AiPersonAbsencesData>
     */
    public function entries(CarbonImmutable $from, CarbonImmutable $to, ?int $craftId, ?string $search): array
    {
        $vacations = Vacation::query()
            ->without(['series', 'conflicts'])
            ->whereIn('vacationer_type', self::CLASSES)
            ->whereDate('date', '>=', $from->toDateString())
            ->whereDate('date', '<=', $to->toDateString())
            ->whereIn('type', [VacationType::OFF_WORK->value, VacationType::NOT_AVAILABLE->value])
            ->orderBy('date')
            ->orderBy('start_time')
            ->get()
            ->groupBy(
                static fn (Vacation $vacation): string => $vacation->vacationer_type . '#' . $vacation->vacationer_id
            );

        $entries = [];

        foreach ($vacations as $rows) {
            /** @var Vacation $first */
            $first = $rows->first();
            /** @var class-string<User|Freelancer|ServiceProvider> $class */
            $class = $first->vacationer_type;
            $person = $class::query()->with(['assignedCrafts', 'shiftQualifications'])->find($first->vacationer_id);

            if (!$person instanceof Model || !$this->matches($person, $craftId, $search)) {
                continue;
            }

            $entries[] = new AiPersonAbsencesData($this->people->personData($person), $this->periods($rows));
        }

        usort(
            $entries,
            static fn (AiPersonAbsencesData $a, AiPersonAbsencesData $b): int => strcmp(
                mb_strtolower($a->person->name),
                mb_strtolower($b->person->name)
            )
        );

        return $entries;
    }

    public static function typeOf(Vacation $vacation): string
    {
        $type = $vacation->type instanceof VacationType ? $vacation->type->value : (string) $vacation->type;

        return $type === VacationType::NOT_AVAILABLE->value ? self::TYPE_NOT_AVAILABLE : self::TYPE_VACATION;
    }

    private function matches(Model $person, ?int $craftId, ?string $search): bool
    {
        /** @var User|Freelancer|ServiceProvider $person */
        if ($craftId !== null && !$person->assignedCrafts->contains('id', $craftId)) {
            return false;
        }

        return $search === null || mb_stripos(AiPeopleService::nameOf($person), $search) !== false;
    }

    /**
     * @param Collection<int, Vacation> $rows nach Datum sortiert
     * @return list<AiAbsencePeriodData>
     */
    private function periods(Collection $rows): array
    {
        /** @var list<array{from: string, to: string, full_day: bool, start: string|null, end: string|null, type: string}> $periods */
        $periods = [];

        foreach ($rows as $vacation) {
            $date = CarbonImmutable::parse($vacation->date)->toDateString();
            $type = self::typeOf($vacation);
            $fullDay = (bool) $vacation->full_day || !$vacation->start_time || !$vacation->end_time;
            $lastIndex = count($periods) - 1;

            if (
                $fullDay && $lastIndex >= 0
                && $periods[$lastIndex]['full_day'] && $periods[$lastIndex]['type'] === $type
                && CarbonImmutable::parse($periods[$lastIndex]['to'])->addDay()->toDateString() === $date
            ) {
                $periods[$lastIndex]['to'] = $date;
                continue;
            }

            $periods[] = [
                'from' => $date,
                'to' => $date,
                'full_day' => $fullDay,
                'start' => $fullDay ? null : $vacation->start_time?->format('H:i'),
                'end' => $fullDay ? null : $vacation->end_time?->format('H:i'),
                'type' => $type,
            ];
        }

        return array_map(static fn (array $period): AiAbsencePeriodData => new AiAbsencePeriodData(
            from: $period['from'],
            to: $period['to'],
            full_day: $period['full_day'],
            start_time: $period['start'],
            end_time: $period['end'],
            type: $period['type'],
        ), $periods);
    }
}
