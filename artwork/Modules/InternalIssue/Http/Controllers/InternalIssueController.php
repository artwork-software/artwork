<?php

namespace Artwork\Modules\InternalIssue\Http\Controllers;

use App\Http\Controllers\Controller;
use Artwork\Modules\InternalIssue\Http\Requests\StoreInternalIssueRequest;
use Artwork\Modules\InternalIssue\Http\Requests\UpdateInternalIssueRequest;
use Artwork\Modules\InternalIssue\Models\InternalIssue;
use Artwork\Modules\InternalIssue\Models\InternalIssueFile;
use Artwork\Modules\InternalIssue\Services\InternalIssueService;
use Artwork\Modules\Inventory\Models\InventoryArticle;
use Artwork\Modules\Inventory\Services\InventoryUserFilterService;
use Artwork\Modules\Inventory\Services\InventoryUserFilterShareService;
use Artwork\Modules\MaterialSet\Models\MaterialSet;
use Illuminate\Http\JsonResponse;
use Inertia\Inertia;
use Illuminate\Auth\AuthManager;

class InternalIssueController extends Controller
{
    public function __construct(
        protected InternalIssueService $internalIssueService,
        protected InventoryUserFilterShareService $inventoryUserFilterShareService,
        protected AuthManager $authManger,
        protected InventoryUserFilterService $inventoryUserFilterService
    ) {}

    public function index(): \Inertia\Response
    {
        $entitiesPerPage = request()?->input('entitiesPerPage', 10);
        $articleIds = request()?->input('article_ids', []);

        // if articleIds is provided, filter issues by articles
        if (!empty($articleIds)) {
            $issues = InternalIssue::with(['files', 'articles', 'specialItems', 'room', 'project', 'responsibleUsers'])
                ->whereHas('articles', function ($query) use ($articleIds) {
                    $query->whereIn('inventory_articles.id', [$articleIds]);
                })
                ->orderBy('start_date')
                ->orderBy('start_time')
                ->paginate($entitiesPerPage);
        } else {
            $issues = InternalIssue::with(['files', 'articles', 'specialItems', 'room', 'project', 'responsibleUsers'])
                ->orderBy('start_date')
                ->orderBy('start_time')
                ->paginate($entitiesPerPage);
        }
        $this->inventoryUserFilterShareService->getFilterDataForUser($this->authManger->user());

        return Inertia::render('IssueOfMaterial/IssueOfMaterialManagement', [
            'issues' => $issues,
            'articlesInFilter' => $articleIds ? InventoryArticle::whereIn('id', [$articleIds])
                ->get() : [],
            'materialSets' => MaterialSet::with('items.article', 'items.article.category', 'items.article.subCategory')->get(),
            'detailedArticle' => Inertia::optional(fn () =>
                InventoryArticle::with([
                    'category',
                    'subCategory',
                    'properties',
                    'images' => function ($query) {
                        $query->orderBy('is_main_image', 'desc')->orderBy('id');
                    },
                    'statusValues',
                    'detailedArticleQuantities.status',
                ])->find(request()?->get('articleId'))
            ),
        ]);
    }

    public function store(StoreInternalIssueRequest $request): \Illuminate\Http\RedirectResponse
    {
        $issue = $this->internalIssueService
            ->store($request->validated(), $request->file('files', []));

        return redirect()->route('issue-of-material.index');
    }

    public function update(UpdateInternalIssueRequest $request, InternalIssue $internalIssue): \Illuminate\Http\RedirectResponse
    {
        //dd($request->all());
        $issue = $this->internalIssueService
            ->update($internalIssue, $request->validated(), $request->file('files', []));

        return redirect()->route('issue-of-material.index');
    }

    public function destroy(InternalIssue $internalIssue): \Illuminate\Http\RedirectResponse
    {
        $this->internalIssueService
            ->delete($internalIssue);

        return redirect()->route('issue-of-material.index');
    }

    public function fileDelete(InternalIssueFile $internalIssueFile): \Illuminate\Http\JsonResponse
    {
        $this->internalIssueService
            ->deleteFile($internalIssueFile);

        return response()->json(['message' => 'File deleted successfully'], 200);
    }

    public function setSpecialItemsDone(InternalIssue $internalIssue): \Illuminate\Http\RedirectResponse
    {
        $internalIssue->update(['special_items_done' => true]);

        return redirect()->route('issue-of-material.index');
    }
}

