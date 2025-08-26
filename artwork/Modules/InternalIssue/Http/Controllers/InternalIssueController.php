<?php

namespace Artwork\Modules\InternalIssue\Http\Controllers;

use App\Http\Controllers\Controller;
use Artwork\Modules\InternalIssue\Http\Requests\StoreInternalIssueRequest;
use Artwork\Modules\InternalIssue\Http\Requests\UpdateInternalIssueRequest;
use Artwork\Modules\InternalIssue\Models\InternalIssue;
use Artwork\Modules\InternalIssue\Models\InternalIssueFile;
use Artwork\Modules\InternalIssue\Services\InternalIssueService;
use Artwork\Modules\Inventory\Models\InventoryArticle;
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
        protected AuthManager $authManger
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
                $this->inventoryUserFilterShareService->getFilterDataForUser($this->authManger->user())
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

