<?php

namespace Artwork\Modules\Crm\Services;

use Artwork\Modules\Crm\Models\CrmContactType;
use Artwork\Modules\Crm\Repositories\CrmContactTypeRepository;
use Illuminate\Database\Eloquent\Collection;

readonly class CrmContactTypeService
{
    public function __construct(
        private CrmContactTypeRepository $repository,
    ) {}

    public function getAll(): Collection
    {
        return $this->repository->getAll();
    }

    public function getAllWithProperties(): Collection
    {
        return $this->repository->getAllWithProperties();
    }

    public function syncProperties(CrmContactType $type, array $properties): void
    {
        $syncData = [];
        foreach ($properties as $prop) {
            $syncData[$prop['id']] = [
                'is_required' => $prop['is_required'] ?? false,
                'show_in_list' => $prop['show_in_list'] ?? false,
                'is_filterable' => $prop['is_filterable'] ?? false,
            ];
        }

        if ($type->is_system) {
            $systemProps = $type->properties()
                ->where('is_system', true)
                ->get();

            foreach ($systemProps as $sp) {
                if (!isset($syncData[$sp->id])) {
                    $syncData[$sp->id] = [
                        'is_required' => $sp->pivot->is_required,
                        'show_in_list' => $sp->pivot->show_in_list,
                        'is_filterable' => $sp->pivot->is_filterable,
                    ];
                }
            }
        }

        $type->properties()->sync($syncData);
    }

    public function getActive(): Collection
    {
        return $this->repository->getActive();
    }

    public function getBySlug(string $slug): ?CrmContactType
    {
        return $this->repository->getBySlug($slug);
    }

    public function store(array $data): CrmContactType
    {
        return $this->repository->create($data);
    }

    public function update(CrmContactType $type, array $data): bool
    {
        if ($type->is_system) {
            unset($data['slug']);
        }

        return $this->repository->update($type, $data);
    }

    public function destroy(CrmContactType $type): void
    {
        if ($type->is_system) {
            throw new \RuntimeException('System contact types cannot be deleted.');
        }

        $this->repository->delete($type);
    }
}
