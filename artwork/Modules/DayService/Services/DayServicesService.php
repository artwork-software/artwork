<?php

namespace Artwork\Modules\DayService\Services;

use Artwork\Modules\DayService\Models\DayService;
use Artwork\Modules\DayService\Models\DayServiceable;
use Artwork\Modules\DayService\Repositories\DayServiceRepository;
use Artwork\Modules\Freelancer\Models\Freelancer;
use Artwork\Modules\ServiceProvider\Models\ServiceProvider;
use Artwork\Modules\User\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\UniqueConstraintViolationException;

readonly class DayServicesService
{
    public function __construct(private DayServiceRepository $dayServiceRepository)
    {
    }

    public function getAll(): Collection
    {
        return $this->dayServiceRepository->getAll();
    }

    public function create(array $data): void
    {
        $dayService = new DayService();
        $dayService->name = $data['name'];
        $dayService->icon = $data['icon'];
        $dayService->hex_color = $data['hex_color'];
        $this->dayServiceRepository->save($dayService);
    }

    public function update(DayService $dayService, array $data): void
    {
        $dayService->name = $data['name'];
        $dayService->icon = $data['icon'];
        $dayService->hex_color = $data['hex_color'];
        $this->dayServiceRepository->save($dayService);
    }

    public function delete(DayService $dayService): void
    {
        // Delete all assignments first to remove relationships
        $dayService->users()->detach();
        $dayService->freelancers()->detach();
        $dayService->serviceProviders()->detach();
        // Delete the day service itself
        $this->dayServiceRepository->delete($dayService);
    }

    /**
     * Idempotent: schnelles Klicken im Tagesdienst-Modus schickte dieselbe Zuweisung mehrfach und legte
     * doppelte Zeilen an. Parallele Requests fängt der Unique-Index ab.
     */
    public function attachDayServiceable(DayService $dayService, $dayServiceable, string $date): void
    {
        if ($dayServiceable === null) {
            return;
        }

        $alreadyAssigned = $dayServiceable->dayServices()
            ->wherePivot('date', $date)
            ->whereKey($dayService->id)
            ->exists();
        if ($alreadyAssigned) {
            return;
        }

        try {
            $dayServiceable->dayServices()->attach($dayService->id, ['date' => $date]);
        } catch (UniqueConstraintViolationException) {
            // gleichzeitiger zweiter Klick hat die Zeile schon angelegt
        }
    }

    public function findModelInstance(string $modelType, int $modelId): ?DayServiceable
    {
        $modelClass = $this->getModelClass($modelType);
        if (!$modelClass) {
            return null;
        }

        try {
            $instance = $modelClass::findOrFail($modelId);
            if ($instance instanceof DayServiceable) {
                return $instance;
            }
        } catch (ModelNotFoundException $e) {
            return null;
        }

        return null;
    }

    protected function getModelClass(string $modelType): ?string
    {

        $modelMap = [
            'user' => User::class,
            'freelancer' => Freelancer::class,
            'service_provider' => ServiceProvider::class,
        ];

        return $modelMap[$modelType] ?? null;
    }
}
