<?php

namespace Artwork\Modules\ExternalIssue\Http\Controllers;

use App\Http\Controllers\Controller;
use Artwork\Modules\ExternalIssue\Http\Requests\StoreExternalIssueRequest;
use Artwork\Modules\ExternalIssue\Http\Requests\UpdateExternalIssueRequest;
use Artwork\Modules\ExternalIssue\Models\ExternalIssue;
use Artwork\Modules\ExternalIssue\Services\ExternalIssueService;

class ExternalIssueController extends Controller
{
    public function __construct(protected ExternalIssueService $externalIssueService) {}

    public function index()
    {

    }

    public function store(StoreExternalIssueRequest $request)
    {
        $issue = $this->externalIssueService->store($request->validated(), $request->file('files', []));

    }

    public function update(UpdateExternalIssueRequest $request, ExternalIssue $externalIssue)
    {
        $issue = $this->externalIssueService->update($externalIssue, $request->validated(), $request->file('files', []));

    }

    public function destroy(ExternalIssue $externalIssue)
    {
        $this->externalIssueService->delete($externalIssue);
    }
}
