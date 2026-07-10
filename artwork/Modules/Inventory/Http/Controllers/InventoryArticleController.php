<?php

namespace Artwork\Modules\Inventory\Http\Controllers;

use App\Http\Controllers\Controller;
use Artwork\Modules\ExternalIssue\Models\ExternalIssue;
use Artwork\Modules\InternalIssue\Models\InternalIssue;
use Artwork\Modules\Inventory\Http\Requests\StoreInventoryArticleRequest;
use Artwork\Modules\Inventory\Http\Requests\UpdateInventoryArticleRequest;
use Artwork\Modules\Inventory\Models\InventoryArticle;
use Artwork\Modules\Inventory\Models\InventoryDetailedQuantityArticle;
use Artwork\Modules\Inventory\Models\InventoryPropertyValue;
use Artwork\Modules\Inventory\Services\InventoryArticleService;
use Artwork\Modules\Inventory\Services\InventoryPlanningService;
use Artwork\Modules\Inventory\Services\InventoryUserFilterService;
use Artwork\Modules\Inventory\Services\InventoryUserFilterShareService;
use Artwork\Modules\Project\Enum\ProjectTabComponentEnum;
use Artwork\Modules\Project\Services\ProjectTabService;
use Artwork\Modules\User\Models\User;
use Artwork\Modules\User\Services\UserService;
use Artwork\Modules\Inventory\Models\InventorySubCategory;
use Illuminate\Auth\AuthManager;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Inertia\Inertia;
use Illuminate\Http\Request;

class InventoryArticleController extends Controller
{

    public function __construct(
        private readonly InventoryArticleService $inventoryArticleService,
        private readonly AuthManager $authManager,
        protected InventoryPlanningService $inventoryPlanningService,
        private readonly InventoryUserFilterService $inventoryUserFilterService,
        private readonly InventoryUserFilterShareService $inventoryUserFilterShareService,
        private readonly ProjectTabService $projectTabService,
    ){
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {

        /** @var User $user */
        $user = $this->authManager->user();
        $this->inventoryUserFilterShareService->getFilterDataForUser($user);
        $data = $this->inventoryPlanningService->getAvailabilityData($user);

        if (request()?->has(['article_id', 'date'])) {
            $data['detailsForModal'] = $this->inventoryPlanningService->getDetailsForModal(
                request('article_id'),
                request('date')
            );
        }

        $data['projectMaterialIssueTabId'] = $this->projectTabService
            ->getFirstProjectTabWithTypeIdOrFirstProjectTabId(
                ProjectTabComponentEnum::PROJECT_MATERIAL_ISSUE_COMPONENT
            );

        return Inertia::render('Inventory/InventoryArticlePlanning', $data);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    public function indexTrash(Request $request)
    {
        $search = trim((string) $request->input('search', ''));
        $perPage = (int) $request->input('entitiesPerPage', 25);

        $trashedArticles = InventoryArticle::onlyTrashed()
            ->with([
                'properties',
                'category',
                'subCategory',
                'images' => fn ($query) => $query->withTrashed(),
                'detailedArticleQuantities' => fn ($query) => $query->withTrashed(),
            ])
            ->when($search !== '', fn ($query) => $query->where('name', 'like', '%' . $search . '%'))
            ->orderBy('name')
            ->paginate($perPage)
            ->withQueryString();

        return Inertia::render('Trash/InventoryArticles', [
            'trashedArticles' => $trashedArticles
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreInventoryArticleRequest $request)
    {
        $this->inventoryArticleService->store($request);
    }
    /**
     * Display the specified resource.
     */
    public function show(InventoryArticle $inventoryArticle)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(InventoryArticle $inventoryArticle)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateInventoryArticleRequest $request, InventoryArticle $inventoryArticle)
    {
        $this->inventoryArticleService->update($inventoryArticle, $request);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(InventoryArticle $inventoryArticle)
    {
        $this->inventoryArticleService->delete($inventoryArticle);
    }

    public function forceDelete(int $inventoryArticle): void
    {
        /** @var InventoryArticle $article */
        $article = InventoryArticle::withTrashed()->findOrFail($inventoryArticle);
        $this->inventoryArticleService->forceDelete($article);
    }

    public function forceDeleteAll(): void
    {
        InventoryArticle::onlyTrashed()->each(function ($article) {
            $this->inventoryArticleService->forceDelete($article);
        });
    }

    public function restore(int $inventoryArticle): void
    {
        /** @var InventoryArticle $article */
        $article = InventoryArticle::onlyTrashed()->findOrFail($inventoryArticle);
        $this->inventoryArticleService->restore($article);
    }

    public function availableStock(InventoryArticle $inventoryArticle, string $startDate, string $endDate)
    {
       // get all available stock for the given article
        // get article stock for the given date range in all issues and returns the available stock of the article
        $availableStock = $this->inventoryArticleService->getAvailableStock($inventoryArticle, $startDate, $endDate);
        return response()->json([
            'availableStock' => $availableStock
        ]);
    }

    public function availableStockBatch(Request $request)
    {
        $validated = $request->validate([
            'article_ids'   => ['required', 'array', 'min:1'],
            'article_ids.*' => ['integer', 'distinct', 'exists:inventory_articles,id'],
            'start_date'    => ['required', 'date'],
            'end_date'      => ['required', 'date', 'after_or_equal:start_date'],
            // Uhrzeiten sind optional – nur wenn mitgegeben, wird stundengenau gerechnet
            'start_time'    => ['nullable', 'date_format:H:i'],
            'end_time'      => ['nullable', 'date_format:H:i'],
            'type'          => ['nullable', 'in:intern,extern'],
            'issue_id'      => ['nullable', 'integer', 'min:1'],
        ]);

        $articleIds = array_map('intval', $validated['article_ids']);
        $startDate  = $validated['start_date'];
        $endDate    = $validated['end_date'];
        $startTime  = $validated['start_time'] ?? null;
        $endTime    = $validated['end_time'] ?? null;
        $type       = $validated['type'] ?? null;
        $issueId    = $validated['issue_id'] ?? null;

        // Wenn Zeiten mitgeliefert → stundengenau, sonst ganztägig
        $hasTime = filled($startTime) && filled($endTime);

        $startAt = $hasTime
            ? \Carbon\Carbon::parse("{$startDate} {$startTime}:00")
            : \Carbon\Carbon::parse($startDate)->startOfDay();

        $endAt = $hasTime
            ? \Carbon\Carbon::parse("{$endDate} {$endTime}:59") // inkl. :59, um "bis 10:00" als einschließend zu behandeln
            : \Carbon\Carbon::parse($endDate)->endOfDay();

        // Hilfs-Raws: TIMESTAMP(date, time) – fällt auf 00:00:00 bzw. 23:59:59 zurück, wenn time NULL ist
        $tsInternalStart = DB::raw("TIMESTAMP(start_date, COALESCE(start_time,'00:00:00'))");
        $tsInternalEnd   = DB::raw("TIMESTAMP(COALESCE(end_date,start_date), COALESCE(end_time,'23:59:59'))");

        // External issues have dates only (no separate time columns). Use full-day bounds for overlap checks.
        $tsExternalStart = DB::raw("TIMESTAMP(issue_date, '00:00:00')");
        $tsExternalEnd   = DB::raw("TIMESTAMP(COALESCE(return_date, issue_date), '23:59:59')");

        $articles = InventoryArticle::whereIn('id', $articleIds)
            ->with([
                'statusValues',
                'detailedArticleQuantities.status',
                // Interne Verplanungen: Zeitüberlappung (start <= endAt) && (end >= startAt)
                'internalIssues' => function ($q) use ($tsInternalStart, $tsInternalEnd, $startAt, $endAt) {
                    $q->where($tsInternalStart, '<=', $endAt)
                        ->where(function ($qq) use ($tsInternalEnd, $startAt) {
                            $qq->where($tsInternalEnd, '>=', $startAt)
                                ->orWhereNull('end_date'); // Sicherheit wie bisher, falls offene Enden genutzt werden
                        });
                },
                // Externe Ausgaben (Verleih): gleiche Logik
                'externalIssues' => function ($q) use ($tsExternalStart, $tsExternalEnd, $startAt, $endAt) {
                    $q->where($tsExternalStart, '<=', $endAt)
                        ->where(function ($qq) use ($tsExternalEnd, $startAt) {
                            $qq->where($tsExternalEnd, '>=', $startAt)
                                ->orWhereNull('return_date');
                        });
                },
            ])
            ->get()
            ->keyBy('id');

        $results = [];
        foreach ($articleIds as $id) {
            if (isset($articles[$id])) {
                // Passe ggf. die Signatur deiner Model-Methode an (siehe Kommentar unten)
                $results[$id] = $articles[$id]->getAvailableStock(
                    $startAt,   // exakter Startzeitpunkt (Carbon)
                    $endAt,     // exakter Endzeitpunkt (Carbon)
                    $issueId,
                    $type
                );
            }
        }

        return response()->json(['data' => $results]);
    }

    public function search(Request $request): \Illuminate\Http\JsonResponse
    {
        $search = $request->input('article_search');

        if (empty(trim($search))) {
            return response()->json([], 200); // Oder returniere eine sinnvolle Fehlermeldung
        }

        $articles = InventoryArticle::search($search)
            ->take(50)
            ->get()
            ->load([
                'category',
                'subCategory',
                'properties',
                'detailedArticleQuantities.status',
                'detailedArticleQuantities.properties',
                'images',
            ]);

        return response()->json($articles);
    }

    public function loadArticlesByFilter(Request $request) {
        $startDate = $request->get('start_date');
        $endDate = $request->get('end_date');
        $search = $request->get('search');
        /** @var User $user */
        $user = $this->authManager->user();
        $articlesByFilter = $this->inventoryUserFilterService->getFilteredArticlesNew($user);

        // Apply search filter if provided (search by name, category, or subcategory)
        if (!empty($search)) {
            $articlesByFilter = $articlesByFilter->where(function ($q) use ($search) {
                $q->where('name', 'like', '%' . $search . '%')
                    ->orWhereHas('category', function ($q) use ($search) {
                        $q->where('name', 'like', '%' . $search . '%');
                    })
                    ->orWhereHas('subCategory', function ($q) use ($search) {
                        $q->where('name', 'like', '%' . $search . '%');
                    });
            });
        }

        return response()->json([
            'articles' => $articlesByFilter->with(['category', 'subCategory', 'detailedArticleQuantities.status', 'images', 'statusValues', 'properties', 'tags'])
                ->paginate(15)
        ]);
    }

    public function updateField(Request $request, InventoryArticle $inventoryArticle)
    {
        $fieldRules = [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'quantity' => ['required', 'numeric', 'min:0'],
            'inventory_category_id' => ['required', 'integer', 'exists:inventory_categories,id'],
            'inventory_sub_category_id' => ['nullable', 'integer', 'exists:inventory_sub_categories,id'],
        ];

        $field = $request->input('field');
        $value = $request->input('value');

        if (!is_string($field) || !array_key_exists($field, $fieldRules)) {
            return response()->json(['error' => 'Invalid field.'], 422);
        }

        $validator = Validator::make(['value' => $value], ['value' => $fieldRules[$field]]);
        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()->first()], 422);
        }

        $data = [$field => $value];

        // Keep category and sub category consistent when either side changes.
        if ($field === 'inventory_sub_category_id' && $value !== null) {
            $belongsToCategory = InventorySubCategory::query()
                ->where('id', $value)
                ->where('inventory_category_id', $inventoryArticle->inventory_category_id)
                ->exists();
            if (!$belongsToCategory) {
                return response()->json(['error' => 'Sub category does not belong to the article category.'], 422);
            }
        }

        if ($field === 'inventory_category_id' && $inventoryArticle->inventory_sub_category_id !== null) {
            $stillMatches = InventorySubCategory::query()
                ->where('id', $inventoryArticle->inventory_sub_category_id)
                ->where('inventory_category_id', $value)
                ->exists();
            if (!$stillMatches) {
                $data['inventory_sub_category_id'] = null;
            }
        }

        $inventoryArticle->update($data);

        return response()->json(['success' => true]);
    }

    public function updateDetailedArticleField(Request $request, InventoryDetailedQuantityArticle $inventoryDetailedQuantityArticle)
    {
        $fieldRules = [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'quantity' => ['required', 'numeric', 'min:0'],
            // No DB foreign key on this column — the exists rule is the only guard
            // against silently storing a dead status id (breaks availability sums).
            'inventory_article_status_id' => ['nullable', 'integer', 'exists:inventory_article_statuses,id'],
        ];

        $field = $request->input('field');
        $value = $request->input('value');

        if (!is_string($field) || !array_key_exists($field, $fieldRules)) {
            return response()->json(['error' => 'Invalid field.'], 422);
        }

        $validator = Validator::make(['value' => $value], ['value' => $fieldRules[$field]]);
        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()->first()], 422);
        }

        $inventoryDetailedQuantityArticle->update([$field => $value]);

        return response()->json(['success' => true]);
    }

    public function updateDetailedArticlePropertyValue(
        Request $request,
        InventoryDetailedQuantityArticle $inventoryDetailedQuantityArticle
    ) {
        $validated = $request->validate([
            'property_id' => ['required', 'integer', 'exists:inventory_article_properties,id'],
            'value' => ['nullable', 'max:255'],
        ]);

        $propertyId = $validated['property_id'];
        $value = $validated['value'] ?? null;

        InventoryPropertyValue::updateOrCreate(
            [
                'inventory_propertyable_type' => InventoryDetailedQuantityArticle::class,
                'inventory_propertyable_id' => $inventoryDetailedQuantityArticle->id,
                'inventory_article_property_id' => $propertyId,
            ],
            ['value' => $value ?? '']
        );

        return response()->json(['success' => true]);
    }

    /**
     * Liefert alle Nutzungsdaten für das UsageModal eines Artikels zeitraum
     */
    public function usageData(Request $request)
    {
        $validated = $request->validate([
            'article_id' => ['required', 'integer', 'exists:inventory_articles,id'],
            'start_date' => ['required', 'date_format:Y-m-d'],
            'end_date' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:start_date'],
        ]);

        $articleId = (int) $validated['article_id'];
        $startDate = $validated['start_date'];
        $endDate = $validated['end_date'] ?? $startDate;

        // Cap the range: the planning service iterates day by day — an open
        // range like 0001-01-01..9999-12-31 would loop for millions of days.
        if (Carbon::parse($startDate)->diffInDays(Carbon::parse($endDate)) > 366) {
            return response()->json(['error' => 'Date range must not exceed one year.'], 422);
        }
        // When editing an existing issue, exclude it from the availability math.
        $excludeIssueId = $request->integer('issue_id') ?: null;
        $excludeType = in_array($request->get('type'), ['intern', 'extern'], true) ? $request->get('type') : null;
        $details = $this->inventoryPlanningService->getDetailsForModalRange(
            $articleId,
            $startDate,
            $endDate,
            $excludeIssueId,
            $excludeType
        );
        return response()->json(['data' => $details]);
    }

    /**
     * JSON variant of `getDetailsForModal` (single date).
     *
     * B8: Used by the planning side-panel so cell/bar clicks no longer trigger
     * a full Inertia partial-reload. Returns the same payload shape as the
     * `detailsForModal` prop served by `index()`.
     */
    public function planningCellDetails(Request $request)
    {
        $validated = $request->validate([
            'article_id' => ['required', 'integer', 'exists:inventory_articles,id'],
            'date'       => ['required', 'date'],
        ]);

        return response()->json([
            'data' => $this->inventoryPlanningService->getDetailsForModal(
                (int) $validated['article_id'],
                $validated['date']
            ),
        ]);
    }
}
