<?php

namespace Artwork\Modules\Budget\Repositories;

use Artwork\Core\Database\Repository\BaseRepository;
use Artwork\Modules\Budget\Models\SageNotAssignedData;
use Artwork\Modules\Project\Models\Project;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Query\Builder as BaseBuilder;

class SageNotAssignedDataRepository extends BaseRepository
{
    public function __construct(private readonly SageNotAssignedData $sageNotAssignedData)
    {
    }

    public function getNewModelInstance(): SageNotAssignedData
    {
        return $this->sageNotAssignedData->newInstance();
    }

    public function getNewModelQuery(): BaseBuilder|Builder
    {
        return $this->sageNotAssignedData->newModelQuery();
    }

    public function getTrashed(): Collection
    {
        return SageNotAssignedData::onlyTrashed()->get();
    }

    public function findBySageId(int $sageId): SageNotAssignedData|null
    {
        return SageNotAssignedData::where('sage_id', $sageId)->first();
    }

    public function findBySageIdKtoSollAndKtoHaben(
        int $sageId,
        string $ktoSoll,
        string $ktoHaben
    ): SageNotAssignedData|null {
        return SageNotAssignedData::where('sage_id', $sageId)
            ->where('kto_soll', $ktoSoll)
            ->where('kto_haben', $ktoHaben)
            ->first();
    }

    public function findParentBookingBySageIdKtoSollAndKtoHaben(
        string $sageId,
        string $ktoSoll,
        string $ktoHaben
    ): SageNotAssignedData|null {
        return $this->getNewModelQuery()
            ->where('sage_id', '=', $sageId)
            ->where('kto_soll', '=', $ktoSoll)
            ->where('kto_haben', '=', $ktoHaben)
            ->where('is_collective_booking', '=', true)
            ->first();
    }

    public function getForFrontend(int|null $projectId): Builder
    {
        return SageNotAssignedData::query()
            ->with('findChildren')
            ->whereNull('parent_booking_id')
            ->where(function($q) use ($projectId) {
                $q->where('project_id', $projectId)
                    ->orWhereNull('project_id');
            })
            ->orderBy('buchungsdatum', 'desc');
    }
}
