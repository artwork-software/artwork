<?php

namespace Artwork\Modules\Vacation\Services;

use App\Http\Controllers\SchedulingController;
use App\Models\Freelancer;
use App\Models\User;
use App\Support\Services\NewHistoryService;
use Artwork\Modules\Vacation\Models\Vacation;
use Artwork\Modules\Vacation\Models\Vacationer;
use Artwork\Modules\Vacation\Repository\VacationRepository;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

class VacationService
{
    public function __construct(
        private readonly VacationRepository   $vacationRepository,
        private readonly NewHistoryService    $historyService,
        private readonly SchedulingController $scheduler //@todo refactor
    )
    {
        $this->historyService->setModel(Vacation::class);
    }

    public function create(Vacationer $vacationer, Carbon $from, Carbon $until): Vacation|Model
    {
        $vacation = $vacationer->vacations()->create([
            'from' => $from,
            'until' => $until
        ]);

        $this->createHistory($vacation, 'Verfügbarkeit hinzugefügt');
        $this->announceChanges($vacation);

        return $vacation;
    }

    public function update(Vacation $vacation, Carbon $from, Carbon $until): Vacation
    {
        $oldFrom = $vacation->from;
        $oldUntil = $vacation->until;

        $vacation->from = $from;
        $vacation->until = $until;
        $vacation = $this->vacationRepository->save($vacation);

        $newFrom = $vacation->from;
        $newUntil = $vacation->until;

        if ($oldFrom !== $newFrom) {
            $this->createHistory(
                $vacation,
                'Verfügbarkeit geändert von ' . Carbon::parse($oldFrom)->format('d.m.Y') . ' zu ' . Carbon::parse($newFrom)->format('d.m.Y')
            );
        }

        if ($oldUntil !== $newUntil) {
            $this->createHistory(
                $vacation,
                'Verfügbarkeit geändert bis: ' . Carbon::parse($oldUntil)->format('d.m.Y') . ' zu ' . Carbon::parse($newUntil)->format('d.m.Y')
            );
        }
        $this->announceChanges($vacation);

        return $vacation;
    }

    public function findVacationsByUserId(int $id): Collection
    {
        return $this->vacationRepository->getByIdAndModel($id, User::class);
    }

    public function findVacationsByFreelancerId(int $id): Collection
    {
        return $this->vacationRepository->getByIdAndModel($id, Freelancer::class);
    }

    public function findVacationWithinInterval(Vacationer $vacationer, Carbon $from, Carbon $until): Collection
    {
        return $this->vacationRepository->getVacationWithinInterval($vacationer, $from, $until);
    }

    public function deleteVacationInterval(Vacationer $vacationer, Carbon $from, Carbon $until): void
    {
        $this->vacationRepository->delete($this->findVacationWithinInterval($vacationer, $from, $until));
    }

    public function delete(Vacation $vacation): void
    {
        $this->vacationRepository->delete($vacation);
    }

    protected function createHistory(Vacation $vacation, string $text): void
    {
        $this->historyService->setHistoryText($text);
        $this->historyService->setModelId($vacation->id);
        $this->historyService->setType('vacation');
        $this->historyService->create();
    }

    protected function announceChanges(Vacation $vacation): void
    {
        if(!$vacation->vacationer instanceof User) {
            return;
        }
        $this->scheduler->create(
            $vacation->vacationer_id, 'VACATION_CHANGES', 'USER_VACATIONS', $vacation->vacationer_id
        );
    }
}
