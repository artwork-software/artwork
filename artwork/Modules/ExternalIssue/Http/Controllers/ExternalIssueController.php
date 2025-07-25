<?php

namespace Artwork\Modules\ExternalIssue\Http\Controllers;

use App\Http\Controllers\Controller;
use Artwork\Modules\ExternalIssue\Http\Requests\StoreExternalIssueRequest;
use Artwork\Modules\ExternalIssue\Http\Requests\UpdateExternalIssueRequest;
use Artwork\Modules\ExternalIssue\Models\ExternalIssue;
use Artwork\Modules\ExternalIssue\Models\ExternalIssueFile;
use Artwork\Modules\ExternalIssue\Services\ExternalIssueService;
use Artwork\Modules\InternalIssue\Models\InternalIssueFile;
use Artwork\Modules\Inventory\Models\InventoryArticle;
use Artwork\Modules\MaterialSet\Models\MaterialSet;
use Artwork\Modules\User\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Auth\AuthManager;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ExternalIssueController extends Controller
{
    public function __construct(
        protected ExternalIssueService $externalIssueService,
        protected AuthManager $auth
    ) {}

    public function index()
    {
        $entitiesPerPage = request()?->input('entitiesPerPage', 10);
        $articleIds = request()?->input('article_ids', []);

        // if articleIds is provided, filter issues by articles
        if (!empty($articleIds)) {
            $issues = ExternalIssue::with(['files', 'articles', 'specialItems', 'issuedBy', 'receivedBy'])
                ->whereHas('articles', function ($query) use ($articleIds) {
                    $query->whereIn('inventory_articles.id', [$articleIds]);
                })
                ->orderBy('issue_date')
                ->orderBy('return_date')
                ->paginate($entitiesPerPage);
        } else {
            $issues = ExternalIssue::with(['files', 'articles', 'specialItems', 'issuedBy', 'receivedBy'])
                ->orderBy('issue_date')
                ->orderBy('return_date')
                ->paginate($entitiesPerPage);
        }

        return Inertia::render('IssueOfMaterial/ExternIssueOfMaterialManagement', [
            'issues' => $issues,
            'articlesInFilter' => $articleIds ? InventoryArticle::whereIn('id', [$articleIds])
                ->get() : [],
            'materialSets' => MaterialSet::with('items.article', 'items.article.category', 'items.article.subCategory')->get(),
        ]);
    }

    public function store(StoreExternalIssueRequest $request)
    {
        /** @var User $user */
        $user = $this->auth->user();
        $issue = $this->externalIssueService->store($request->validated(), $user, $request->file('files', []));

    }

    public function update(UpdateExternalIssueRequest $request, ExternalIssue $externalIssue)
    {
        $issue = $this->externalIssueService->update($externalIssue, $request->validated(), $request->file('files', []));
    }

    public function destroy(ExternalIssue $externalIssue)
    {
        $this->externalIssueService->delete($externalIssue);
    }

    public function returnExternal(ExternalIssue $externalIssue, Request $request)
    {
        $externalIssue->update([
            'received_by_id' => $this->auth->user()->id,
            'return_remarks' => $request->input('return_remarks'),
        ]);
    }

    public function setSpecialItemsDone(ExternalIssue $externalIssue)
    {
        $externalIssue->update(['special_items_done' => true]);
    }

    public function print(ExternalIssue $externalIssue)
    {
        $externalIssue->load(['articles.category', 'articles.subCategory', 'specialItems.category', 'specialItems.subCategory', 'files', 'issuedBy', 'receivedBy']);

        $pdf = Pdf::loadView('pdf.external_issue', ['issue' => $externalIssue]);
        return $pdf->download('leihschein_' . $externalIssue->id . '.pdf');
    }

    public function fileDelete(ExternalIssueFile $externalIssueFile): void
    {
        $this->externalIssueService
            ->deleteFile($externalIssueFile);
    }
}
