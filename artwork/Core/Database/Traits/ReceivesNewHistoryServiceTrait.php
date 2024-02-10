<?php

namespace Artwork\Core\Database\Traits;

use App\Support\Services\NewHistoryService;

trait ReceivesNewHistoryServiceTrait
{
    private function getNewHistoryService(string $modelObject): NewHistoryService
    {
        return new NewHistoryService($modelObject);
    }
}
