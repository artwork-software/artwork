<?php

namespace Artwork\Modules\DayService\Services;

use Artwork\Modules\DayService\Models\DayService;
use Artwork\Modules\DayService\Repositories\DayServiceRepository;
use Illuminate\Database\Eloquent\Collection;

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
}
