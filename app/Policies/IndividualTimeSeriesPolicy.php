<?php

namespace App\Policies;

use Artwork\Modules\Freelancer\Models\Freelancer;
use Artwork\Modules\IndividualTimes\Models\IndividualTimeSeries;
use Artwork\Modules\Permission\Enums\PermissionEnum;
use Artwork\Modules\ServiceProvider\Models\ServiceProvider;
use Artwork\Modules\User\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

/**
 * Prüfung pro betroffener Person wie IndividualTimePolicy: eigene Zeiten immer, fremde Nutzer*innen
 * mit "can manage availability", Worker zusätzlich mit den Manager-Rechten.
 */
class IndividualTimeSeriesPolicy
{
    use HandlesAuthorization;

    /**
     * @param array<int, array{type: string, id: int|string}> $subjects Payload-Subjects
     *        (type: user|freelancer|service_provider)
     */
    public function createForSubjects(User $user, array $subjects): bool
    {
        return $this->managesAllSubjects($user, $subjects);
    }

    /**
     * Erlaubt, wenn mindestens eine Person der Serie verwaltbar ist.
     */
    public function view(User $user, IndividualTimeSeries $individualTimeSeries): bool
    {
        foreach ($this->subjectsOf($individualTimeSeries) as $subject) {
            if (self::managesSubject($user, (string) $subject['type'], (int) $subject['id'])) {
                return true;
            }
        }

        return false;
    }

    /**
     * Geprüft werden die Subjects des Payloads (Update legt eine neue Serie an).
     *
     * @param array<int, array{type: string, id: int|string}> $subjects
     */
    public function update(User $user, IndividualTimeSeries $individualTimeSeries, array $subjects = []): bool
    {
        return $this->managesAllSubjects($user, $subjects);
    }

    /**
     * Jede Person der Serie muss verwaltbar sein.
     */
    public function delete(User $user, IndividualTimeSeries $individualTimeSeries): bool
    {
        return $this->managesAllSubjects($user, $this->subjectsOf($individualTimeSeries));
    }

    /**
     * @return array<int, array{type: string, id: int}>
     */
    private function subjectsOf(IndividualTimeSeries $individualTimeSeries): array
    {
        return $individualTimeSeries->individualTimes()
            ->select(['timeable_type', 'timeable_id'])
            ->distinct()
            ->get()
            ->map(static fn ($row) => ['type' => (string) $row->timeable_type, 'id' => (int) $row->timeable_id])
            ->all();
    }

    /**
     * @param array<int, array{type: string, id: int|string}> $subjects
     */
    private function managesAllSubjects(User $user, array $subjects): bool
    {
        if ($subjects === []) {
            return false;
        }

        foreach ($subjects as $subject) {
            if (!self::managesSubject($user, (string) ($subject['type'] ?? ''), (int) ($subject['id'] ?? 0))) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param string $type "user"|"freelancer"|"service_provider" oder Model-Klassenname (timeable_type)
     */
    public static function managesSubject(User $user, string $type, int $id): bool
    {
        $isUser = in_array($type, ['user', User::class], true);
        $isWorker = in_array(
            $type,
            ['freelancer', Freelancer::class, 'service_provider', ServiceProvider::class],
            true
        );

        if (!$isUser && !$isWorker) {
            return false;
        }

        if ($isUser && $id === (int) $user->id) {
            return true;
        }

        if ($user->can(PermissionEnum::AVAILABILITY_MANAGEMENT->value)) {
            return true;
        }

        return $isWorker && $user->canAny([
            PermissionEnum::MA_MANAGER->value,
            PermissionEnum::EXTERNAL_MANAGER->value,
        ]);
    }
}
