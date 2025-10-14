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
        $entitiesPerPage = (int) request()->input('entitiesPerPage', 10);
        $q = trim((string) request()->input('q', ''));

        // Artikel-IDs robust parsen
        $articleIdsInput = request()->input('article_ids', '');
        $articleIds = [];
        if (is_array($articleIdsInput)) {
            $articleIds = array_filter(array_map('intval', $articleIdsInput));
        } elseif (is_string($articleIdsInput) && strlen($articleIdsInput)) {
            $articleIds = array_filter(array_map('intval', explode(',', $articleIdsInput)));
        }

        // Filter
        $dateFrom  = request()->input('date_from'); // Y-m-d
        $dateTo    = request()->input('date_to');   // Y-m-d
        $projectId = (int) request()->input('project_id', 0);
        $roomId    = (int) request()->input('room_id', 0);

        // Mehrfach-User
        $respInput = request()->input('responsible_user_ids', '');
        $responsibleUserIds = [];
        if (is_array($respInput)) {
            $responsibleUserIds = array_filter(array_map('intval', $respInput));
        } elseif (is_string($respInput) && strlen($respInput)) {
            $responsibleUserIds = array_filter(array_map('intval', explode(',', $respInput)));
        }

        $issuesQuery = InternalIssue::query()
            ->with([
                'files',
                'articles.images',
                'articles.category',
                'articles.subCategory',
                'specialItems',
                'room',
                'project',
                'responsibleUsers',
            ])
            ->when(!empty($articleIds), function ($q) use ($articleIds) {
                $q->whereHas('articles', function ($sub) use ($articleIds) {
                    $sub->whereIn('inventory_articles.id', $articleIds);
                });
            })
            ->when($projectId > 0, fn ($q) => $q->where('project_id', $projectId))
            ->when($roomId > 0, fn ($q) => $q->where('room_id', $roomId))
            ->when(!empty($responsibleUserIds), function ($q) use ($responsibleUserIds) {
                $q->whereHas('responsibleUsers', function ($sub) use ($responsibleUserIds) {
                    $sub->whereIn('users.id', $responsibleUserIds);
                });
            })

            // 🔁 NEU: Zeitraum-Overlap statt Startdatum-AND-Filter
            ->overlapping($dateFrom, $dateTo)

            // Suche
            ->when($q !== '', function ($qbuilder) use ($q) {
                $qbuilder->where(function ($sub) use ($q) {
                    $sub->where('name', 'like', "%{$q}%")
                        ->orWhere('notes', 'like', "%{$q}%");
                });
            })
            ->orderBy('start_date')
            ->orderBy('start_time');

        $issues = $issuesQuery->paginate($entitiesPerPage)->withQueryString();

        $articlesInFilter = !empty($articleIds)
            ? InventoryArticle::whereIn('id', $articleIds)->get()
            : collect([]);

        // ⚠️ Tippfehler korrigiert: authManager (nicht authManger)
        $this->inventoryUserFilterShareService->getFilterDataForUser($this->authManger->user());

        return Inertia::render('IssueOfMaterial/IssueOfMaterialManagement', [
            'issues' => $issues,
            'articlesInFilter' => $articlesInFilter,
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
            ])->find(request()->get('articleId'))
            ),
            'urlParameters' => request()->only([
                'article_ids','date_from','date_to','project_id','room_id','responsible_user_ids','q'
            ]),
        ]);
    }


    public function store(StoreInternalIssueRequest $request): \Illuminate\Http\RedirectResponse
    {
        $issue = $this->internalIssueService
            ->store($request->validated(), $request->file('files', []));

        return redirect()->back();
    }

    public function update(UpdateInternalIssueRequest $request, InternalIssue $internalIssue): \Illuminate\Http\RedirectResponse
    {
        //dd($request->all());
        $issue = $this->internalIssueService
            ->update($internalIssue, $request->validated(), $request->file('files', []));

        return redirect()->back();
    }

    public function destroy(InternalIssue $internalIssue): \Illuminate\Http\RedirectResponse
    {
        $this->internalIssueService
            ->delete($internalIssue);

        return redirect()->back();
    }

    public function fileDelete(InternalIssueFile $internalIssueFile): \Illuminate\Http\RedirectResponse
    {
        $this->internalIssueService
            ->deleteFile($internalIssueFile);

        return redirect()->back();
    }

    public function setSpecialItemsDone(InternalIssue $internalIssue): \Illuminate\Http\RedirectResponse
    {
        $internalIssue->update(['special_items_done' => true]);

        return redirect()->back();
    }
}

