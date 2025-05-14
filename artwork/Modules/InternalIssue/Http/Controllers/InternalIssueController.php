<?php

namespace Artwork\Modules\InternalIssue\Http\Controllers;

use App\Http\Controllers\Controller;
use Artwork\Modules\InternalIssue\Http\Requests\StoreInternalIssueRequest;
use Artwork\Modules\InternalIssue\Http\Requests\UpdateInternalIssueRequest;
use Artwork\Modules\InternalIssue\Models\InternalIssue;
use Artwork\Modules\InternalIssue\Services\InternalIssueService;
use Illuminate\Http\JsonResponse;

class InternalIssueController extends Controller
{
    public function __construct(protected InternalIssueService $internalIssueService) {}

    public function index(): JsonResponse
    {
        $issues = InternalIssue::with(['files', 'articles', 'specialItems'])->get();
    }

    public function store(StoreInternalIssueRequest $request): JsonResponse
    {
        $issue = $this->internalIssueService
            ->store($request->validated(), $request->file('files', []));
    }

    public function update(UpdateInternalIssueRequest $request, InternalIssue $internalIssue): JsonResponse
    {
        $issue = $this->internalIssueService
            ->update($internalIssue, $request->validated(), $request->file('files', []));
    }

    public function destroy(InternalIssue $internalIssue): JsonResponse
    {
        $this->internalIssueService
            ->delete($internalIssue);
    }
}
