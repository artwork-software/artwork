<?php

namespace Artwork\Modules\AiAssistant\Services;

use Artwork\Modules\AiAssistant\DTOs\AiRefData;
use Artwork\Modules\AiAssistant\DTOs\AiWorkTimeData;
use Artwork\Modules\AiAssistant\DTOs\AiWorkTimeOverviewData;
use Artwork\Modules\AiAssistant\DTOs\AiWorkTimeOverviewEntryData;
use Artwork\Modules\AiAssistant\Support\AiErrors;
use Artwork\Modules\AiAssistant\Support\AiText;
use Artwork\Modules\Craft\Models\Craft;
use Artwork\Modules\Permission\Enums\PermissionEnum;
use Artwork\Modules\Role\Enums\RoleEnum;
use Artwork\Modules\User\Models\User;
use Artwork\Modules\WorkTime\Services\OvertimeService;
use Artwork\Modules\WorkTime\Services\WorkTimeCalculationService;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Symfony\Component\HttpFoundation\Response;

/**
 * Arbeitszeitkonto einer Person (ai-scenarios-3 S7) und Stundenuebersicht im Team (ai-scenarios-4 S16).
 *
 * Monatswerte aus `WorkTimeCalculationService::breakdownForRange()` + `summarizeRange()` (dieselbe
 * Rechnung wie die Arbeitszeitenansicht), Kontostand aus `OvertimeService::ledgerFor()`.
 * Nur Nutzer:innen haben ein Konto (sonst 422 `no_work_time_account`). Sehen darf man das eigene
 * Konto oder mit „Stunden der Schichtmitarbeitenden sehen" bzw. als Admin (sonst 403
 * `no_work_time_access`). Die Uebersicht verlangt das Recht immer.
 */
class AiWorkTimeService
{
    public const SORT_BALANCE = 'balance';

    public const SORT_DIFFERENCE = 'difference';

    /**
     * Hoechstens so viele Personen werden fuer die Uebersicht gerechnet (Kontostand = Replay aller
     * Buchungen je Person); sortiert und gekappt wird danach.
     */
    public const MAX_EVALUATED = 200;

    public function __construct(
        private readonly AiPeopleService $people,
        private readonly WorkTimeCalculationService $workTime,
        private readonly OvertimeService $overtime,
    ) {
    }

    public function workTime(User $viewer, string $type, int $id, CarbonImmutable $monthStart): AiWorkTimeData
    {
        if ($type !== AiPeopleService::TYPE_USER) {
            $this->people->find($type, $id);
            AiErrors::abort(AiErrors::NO_WORK_TIME_ACCOUNT, Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        /** @var User $person */
        $person = $this->people->find($type, $id);

        if ($viewer->id !== $person->id && !self::mayViewHours($viewer)) {
            AiErrors::abort(AiErrors::NO_WORK_TIME_ACCESS);
        }

        $values = $this->monthValues($person, $monthStart);
        $ledger = $values['ledger'];
        $ruleActive = (bool) ($this->overtime->ruleSettingsToday($person)['rule_active'] ?? false);

        return new AiWorkTimeData(
            person: $this->people->personData($person),
            month: $monthStart->format('Y-m'),
            target_minutes: $values['target'],
            actual_minutes: $values['actual'],
            difference_minutes: $values['difference'],
            balance_minutes: $ledger->balanceMinutes(),
            open_minutes: $ledger->openMinutes(),
            payable_minutes: $ruleActive ? $ledger->payableNowMinutes() : null,
        );
    }

    /**
     * Stundenuebersicht (S16): Nutzer:innen mit Arbeitszeitkonto (mindestens eine
     * Vertragszuordnung), optional im Gewerk; absteigend nach Kontostand bzw. Differenz Ist − Soll
     * (unbekannte Differenz zuletzt).
     */
    public function overview(
        User $viewer,
        CarbonImmutable $monthStart,
        ?int $craftId,
        string $sort,
        ?int $limit
    ): AiWorkTimeOverviewData {
        if (!self::mayViewHours($viewer)) {
            AiErrors::abort(AiErrors::NO_WORK_TIME_ACCESS);
        }

        $craft = $craftId === null ? null : Craft::query()->find($craftId);
        abort_if($craftId !== null && $craft === null, Response::HTTP_NOT_FOUND);

        $max = (int) config('ai.list_limit');
        $limit = $limit === null ? $max : min(max(1, $limit), $max);

        $query = User::query()
            ->whereHas('contractAssigns')
            ->when(
                $craftId !== null,
                static fn (Builder $builder) => $builder->whereHas(
                    'assignedCrafts',
                    static fn (Builder $crafts) => $crafts->where('crafts.id', $craftId)
                )
            );
        $total = (clone $query)->count();

        $entries = [];
        foreach (
            $query->with(['assignedCrafts', 'shiftQualifications'])
                ->orderBy('last_name')
                ->orderBy('first_name')
                ->limit(self::MAX_EVALUATED)
                ->get() as $person
        ) {
            $values = $this->monthValues($person, $monthStart);
            $entries[] = new AiWorkTimeOverviewEntryData(
                person: $this->people->personData($person),
                target_minutes: $values['target'],
                actual_minutes: $values['actual'],
                difference_minutes: $values['difference'],
                balance_minutes: $values['ledger']->balanceMinutes(),
            );
        }

        usort($entries, static function (
            AiWorkTimeOverviewEntryData $a,
            AiWorkTimeOverviewEntryData $b
        ) use ($sort): int {
            $valueA = $sort === self::SORT_DIFFERENCE ? $a->difference_minutes : $a->balance_minutes;
            $valueB = $sort === self::SORT_DIFFERENCE ? $b->difference_minutes : $b->balance_minutes;

            // Absteigend nach Wert, unbekannte Werte zuletzt, dann Name.
            return [$valueA === null ? 1 : 0, -($valueA ?? 0), $a->person->name]
                <=> [$valueB === null ? 1 : 0, -($valueB ?? 0), $b->person->name];
        });

        return new AiWorkTimeOverviewData(
            month: $monthStart->format('Y-m'),
            craft: $craft === null ? null : new AiRefData((int) $craft->id, AiText::truncate($craft->name)),
            sort: $sort,
            data: array_slice($entries, 0, $limit),
            total: $total,
            limit: $limit,
            truncated: $total > $limit,
        );
    }

    public static function mayViewHours(User $viewer): bool
    {
        return $viewer->hasRole(RoleEnum::ARTWORK_ADMIN->value)
            || $viewer->checkPermissionTo(PermissionEnum::CAN_VIEW_SHIFT_WORKER_HOURS->value, 'web');
    }

    /**
     * @return array{
     *     target: int|null, actual: int, difference: int|null,
     *     ledger: \Artwork\Modules\WorkTime\Support\OvertimeLedger
     * }
     */
    private function monthValues(User $person, CarbonImmutable $monthStart): array
    {
        $summary = WorkTimeCalculationService::summarizeRange($this->workTime->breakdownForRange(
            $person,
            Carbon::parse($monthStart->toDateString()),
            Carbon::parse($monthStart->endOfMonth()->toDateString()),
        ));
        $actual = (int) $summary['actual'];

        return [
            'target' => $summary['target'],
            'actual' => $actual,
            'difference' => $summary['target'] === null ? null : $actual - (int) $summary['target'],
            'ledger' => $this->overtime->ledgerFor($person),
        ];
    }
}
