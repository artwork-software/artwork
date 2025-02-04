<?php

namespace Artwork\Modules\ProjectPrintLayout\Repositories;

use Artwork\Modules\ProjectPrintLayout\Models\ProjectPrintLayout;
use Illuminate\Database\Eloquent\Collection;

readonly class ProjectPrintLayoutRepository
{
    public function getProjectPrintLayouts(): Collection
    {
        return ProjectPrintLayout::with([
            'headerComponents',
            'bodyComponents',
            'footerComponents',
            'headerComponents.component',
            'bodyComponents.component',
            'footerComponents.component',
        ])->get();
    }

    public function getMaxOrder(): int
    {
        return ProjectPrintLayout::max('order') ?? 0;
    }

    public function createProjectPrintLayout(array $data): void
    {
        ProjectPrintLayout::create($data);
    }
}