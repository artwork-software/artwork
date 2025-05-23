<?php

namespace Artwork\Modules\InternalIssue\Http\Controllers;

use App\Http\Controllers\Controller;
use Artwork\Modules\InternalIssue\Http\Requests\StoreInternalIssueRequest;
use Artwork\Modules\InternalIssue\Http\Requests\UpdateInternalIssueRequest;
use Artwork\Modules\InternalIssue\Models\InternalIssue;
use Artwork\Modules\InternalIssue\Models\InternalIssueFile;
use Artwork\Modules\InternalIssue\Services\InternalIssueService;
use Illuminate\Http\JsonResponse;
use Inertia\Inertia;

class InternalIssueController extends Controller
{
    public function __construct(protected InternalIssueService $internalIssueService) {}

    public function index(): \Inertia\Response
    {
        $entitiesPerPage = request()?->input('entitiesPerPage', 10);
        $issues = InternalIssue::with(['files', 'articles', 'specialItems', 'room', 'project', 'responsibleUsers'])
            ->orderBy('start_date')
            ->orderBy('start_time')
            ->paginate($entitiesPerPage);

        return Inertia::render('IssueOfMaterial/IssueOfMaterialManagement', [
            'issues' => $issues,
        ]);
    }

    public function store(StoreInternalIssueRequest $request)
    {
        $issue = $this->internalIssueService
            ->store($request->validated(), $request->file('files', []));

    }

    public function update(UpdateInternalIssueRequest $request, InternalIssue $internalIssue): void
    {
        //dd($request->all());
        $issue = $this->internalIssueService
            ->update($internalIssue, $request->validated(), $request->file('files', []));
    }

    public function destroy(InternalIssue $internalIssue): JsonResponse
    {
        $this->internalIssueService
            ->delete($internalIssue);
    }

    public function fileDelete(InternalIssueFile $internalIssueFile): void
    {
        $this->internalIssueService
            ->deleteFile($internalIssueFile);
    }

    public function setSpecialItemsDone(InternalIssue $internalIssue)
    {
        $internalIssue->update(['special_items_done' => true]);
    }
}
