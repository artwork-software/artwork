<?php

namespace Artwork\Modules\Shift\RuleChecks;

use Artwork\Modules\Shift\Models\ShiftRule;
use Artwork\Modules\Shift\Repositories\CompensationDayOffRepository;
use Artwork\Modules\User\Models\User;
use Artwork\Modules\Vacation\Enums\Vacation as VacationType;
use Artwork\Modules\Vacation\Models\Vacation;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * Ankündigungsfrist freier Tage: Ein freier Tag muss mindestens X Tage vorher feststehen (NV Bühne:
 * Bühnentechnik spätestens zwei Wochen vorher, Solo, Chor und Tanz sechs Wochen vorher).
 *
 * $rule->individual_number_value = Mindestvorlauf in Tagen. Geprüft werden zwei Quellen:
 *  - geplante freie Tage aus dem Tagesstatus "Frei" (Vacation mit Typ FREE_WORK, ganz oder halb):
 *    Tag der Anlage gegen den freien Tag. Wurde ein bestehender Eintrag erst später auf "Frei"
 *    umgestellt, zählt der Tag der Umstellung (status_changed_at)
 *  - gewährte ganze oder halbe Ersatzfreitage: Tag der Gewährung (granted_at) gegen den freien Tag
 * Je freiem Tag zählt der späteste Eintrag, es entsteht höchstens ein Verstoß ohne Schicht am freien Tag.
 *
 * Grenze: Ein Tag, der nur deshalb frei ist, weil kein Dienst geplant wurde, hat keinen
 * Ankündigungszeitpunkt und wird nicht geprüft.
 */
class FreeDayAnnouncementLeadTimeCheck extends AbstractRuleCheck
{
    public function check(ShiftRule $rule, User $user, Carbon $startDate, Carbon $endDate): Collection
    {
        $violations = collect();
        $minDays = (int) round((float) $rule->individual_number_value);
        if ($minDays <= 0) {
            return $violations;
        }

        /** @var array<string, array{announced: Carbon, value: float}> $announcements */
        $announcements = [];
        $remember = static function (Carbon $freeDay, Carbon $announcedOn, float $value) use (&$announcements): void {
            $key = $freeDay->toDateString();
            $known = $announcements[$key] ?? null;
            $announcements[$key] = [
                'announced' => $known !== null && $known['announced']->gt($announcedOn)
                    ? $known['announced']
                    : $announcedOn,
                'value' => min(1.0, ($known['value'] ?? 0.0) + $value),
            ];
        };

        $plannedFreeDays = Vacation::query()
            ->where('vacationer_type', User::class)
            ->where('vacationer_id', $user->id)
            ->where('type', VacationType::FREE_WORK->value)
            ->whereBetween('date', [$startDate->toDateString(), $endDate->toDateString()])
            ->without(['series', 'conflicts'])
            ->get(['id', 'date', 'full_day', 'created_at', 'status_changed_at']);
        foreach ($plannedFreeDays as $plannedFreeDay) {
            $announcedAt = $plannedFreeDay->status_changed_at ?? $plannedFreeDay->created_at;
            if ($announcedAt === null) {
                continue;
            }
            $remember(
                Carbon::parse($plannedFreeDay->date)->startOfDay(),
                $announcedAt->copy()->startOfDay(),
                $plannedFreeDay->full_day ? 1.0 : 0.5
            );
        }

        $daysOff = app(CompensationDayOffRepository::class)->getGrantedForUserInRange(
            $user->id,
            $startDate->toDateString(),
            $endDate->toDateString()
        );
        foreach ($daysOff as $dayOff) {
            if ($dayOff->granted_at === null) {
                continue;
            }
            $remember(
                $dayOff->granted_date->copy()->startOfDay(),
                $dayOff->granted_at->copy()->startOfDay(),
                (float) $dayOff->value
            );
        }

        foreach ($announcements as $dateKey => $announcement) {
            $freeDay = Carbon::parse($dateKey)->startOfDay();
            $leadDays = (int) $announcement['announced']->diffInDays($freeDay, false);
            if ($leadDays >= $minDays) {
                continue;
            }

            $violations->push($this->createViolationWithoutShift($rule, $user, $freeDay, [
                'type' => 'free_day_announcement',
                'lead_days' => $leadDays,
                'min_required' => $minDays,
                'granted_on' => $announcement['announced']->toDateString(),
                'value' => $announcement['value'],
            ]));
        }

        return $violations;
    }

    /**
     * Liest freie Tage und Ersatzfreitage direkt und braucht keinen Datenkontext.
     */
    public function getContextRange(ShiftRule $rule, Carbon $startDate, Carbon $endDate): ?array
    {
        return null;
    }

    public function getTriggerType(): string
    {
        return 'freeDayAnnouncementLeadTime';
    }
}
