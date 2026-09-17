<?php

namespace App\Http\Controllers;

use Artwork\Modules\Worker\Services\WorkerService;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class WorkerController extends Controller
{
    public function __construct(private readonly WorkerService $workerService)
    {
    }

    public function scoutWorkerSearch(Request $request): Collection
    {
        // UserSearch.vue sendet den Suchbegriff als user_search (wie user.scoutSearch); query bleibt als Fallback
        $search = (string) $request->string('user_search', (string) $request->string('query'));

        return $this->workerService->searchWorkers($search);
    }
}
