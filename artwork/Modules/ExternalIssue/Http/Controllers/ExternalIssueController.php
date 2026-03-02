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
use Artwork\Modules\Inventory\Services\InventoryUserFilterShareService;
use Artwork\Modules\MaterialSet\Models\MaterialSet;
use Artwork\Modules\User\Models\User;
use Barryvdh\Snappy\Facades\SnappyPdf;
use Illuminate\Auth\AuthManager;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ExternalIssueController extends Controller
{
    public function __construct(
        protected ExternalIssueService $externalIssueService,
        protected AuthManager $auth,
        protected InventoryUserFilterShareService $inventoryUserFilterShareService,
    ) {}

    public function index()
    {
        $entitiesPerPage = request()?->integer('entitiesPerPage', 10);

        // IDs aus CSV/Array robust einlesen
        $articleIdsParam = request()?->input('article_ids', []);
        $articleIds = is_string($articleIdsParam)
            ? array_filter(array_map('intval', explode(',', $articleIdsParam)))
            : (is_array($articleIdsParam) ? array_map('intval', $articleIdsParam) : []);

        // neue Filter
        $dateFrom = request()?->input('date_from');
        $dateTo   = request()?->input('date_to');
        $issuedBy    = request()?->filled('issued_by_id')    ? (int) request()->input('issued_by_id')    : null;
        $receivedBy  = request()?->filled('received_by_id')  ? (int) request()->input('received_by_id')  : null;

        $q        = trim((string) request()?->input('q', ''));

        $issuesQuery = ExternalIssue::with([
            'files',
            'articles', 'articles.images',
            'specialItems',
            'issuedBy',
            'receivedBy',
        ]);

        if (!empty($articleIds)) {
            $issuesQuery->whereHas('articles', function ($query) use ($articleIds) {
                $query->whereIn('inventory_articles.id', $articleIds);
            });
        }

        // Zeitfilter: entweder im Ausgabedatum ODER im Rückgabedatum innerhalb Range
        $issuesQuery->overlapping($dateFrom, $dateTo);

        // User-Filter
        $issuesQuery
            ->when($issuedBy !== null,   fn($q) => $q->where('issued_by_id',   $issuedBy))
            ->when($receivedBy !== null, fn($q) => $q->where('received_by_id', $receivedBy));

        // Name/Extern/Remarks Suche
        if ($q !== '') {
            $issuesQuery->where(function ($sub) use ($q) {
                $sub->where('name', 'like', "%{$q}%")
                    ->orWhere('external_name', 'like', "%{$q}%")
                    ->orWhere('return_remarks', 'like', "%{$q}%");
            });
        }

        $issues = $issuesQuery
            ->orderBy('issue_date')
            ->orderBy('return_date')
            ->paginate($entitiesPerPage);

        $this->inventoryUserFilterShareService->getFilterDataForUser($this->auth->user());

        return Inertia::render('IssueOfMaterial/ExternIssueOfMaterialManagement', [
            'issues' => $issues,
            'articlesInFilter' => !empty($articleIds)
                ? InventoryArticle::whereIn('id', $articleIds)->get()
                : [],
            'materialSets' => MaterialSet::with('items.article', 'items.article.category', 'items.article.subCategory')->get(),
            'detailedArticle' => Inertia::optional(fn () =>
            InventoryArticle::with([
                'category',
                'subCategory',
                'properties',
                'images' => fn ($q) => $q->orderBy('is_main_image', 'desc')->orderBy('id'),
                'statusValues',
                'detailedArticleQuantities.status',
            ])->find(request()?->get('articleId'))
            ),
            // optional, falls du urlParameters nutzt:
            'urlParameters' => request()->only([
                'article_ids','date_from','date_to','issued_by_id','received_by_id','q'
            ]),
        ]);
    }


    public function store(StoreExternalIssueRequest $request): \Illuminate\Http\RedirectResponse
    {
        $issue = $this->externalIssueService->store($request->validated(), $request->file('files', []));
        return redirect()->route('extern-issue-of-material.index');
    }

    public function update(UpdateExternalIssueRequest $request, ExternalIssue $externalIssue): \Illuminate\Http\RedirectResponse
    {
        $issue = $this->externalIssueService->update($externalIssue, $request->validated(), $request->file('files', []));

        return redirect()->route('extern-issue-of-material.index');
    }

    public function destroy(ExternalIssue $externalIssue): \Illuminate\Http\RedirectResponse
    {
        $this->externalIssueService->delete($externalIssue);

        return redirect()->route('extern-issue-of-material.index');
    }

    public function returnExternal(ExternalIssue $externalIssue, Request $request): \Illuminate\Http\RedirectResponse
    {
        $externalIssue->update([
            'received_by_id' => $this->auth->user()->id,
            'return_remarks' => $request->input('return_remarks'),
        ]);

        return redirect()->route('extern-issue-of-material.index');
    }

    public function setSpecialItemsDone(ExternalIssue $externalIssue): \Illuminate\Http\RedirectResponse
    {
        $externalIssue->update(['special_items_done' => true]);

        return redirect()->route('extern-issue-of-material.index');
    }

    public function print(ExternalIssue $externalIssue)
    {
        $externalIssue->load(['articles.category', 'articles.subCategory', 'specialItems.category', 'specialItems.subCategory', 'files', 'issuedBy', 'receivedBy']);

        $pdf = SnappyPdf::loadView('pdf.external_issue', ['issue' => $externalIssue]);
        return $pdf->download('leihschein_' . $externalIssue->id . '.pdf');
    }

    public function fileDelete(ExternalIssueFile $externalIssueFile): \Illuminate\Http\JsonResponse
    {
        $this->externalIssueService
            ->deleteFile($externalIssueFile);

        return response()->json(['message' => 'File deleted successfully'], 200);
    }
}
