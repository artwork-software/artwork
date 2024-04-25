<?php

namespace Artwork\Modules\TaskTemplate\Services;

use Artwork\Modules\TaskTemplate\Repositories\TaskTemplateRepository;

readonly class TaskTemplateService
{
    public function __construct(private TaskTemplateRepository $taskTemplateRepository)
    {
    }
}
