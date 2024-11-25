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
        return $this->workerService->searchWorkers($request->string('query'));
    }
}
