<?php

namespace Artwork\Modules\ChecklistTemplate\Services;

use Artwork\Modules\ChecklistTemplate\Repositories\ChecklistTemplateRepository;

readonly class ChecklistTemplateService
{
    public function __construct(private ChecklistTemplateRepository $checklistTemplateRepository)
    {
    }
}
