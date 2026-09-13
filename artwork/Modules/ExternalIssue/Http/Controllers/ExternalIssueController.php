<?php

namespace Artwork\Modules\ExternalIssue\Http\Controllers;

use App\Http\Controllers\Controller;
use Artwork\Core\FileHandling\Naming\StoredFileName;
use Artwork\Modules\ExternalIssue\Http\Requests\StoreExternalIssueRequest;
use Artwork\Modules\ExternalIssue\Http\Requests\UpdateExternalIssueRequest;
use Artwork\Modules\ExternalIssue\Models\ExternalIssue;
use Artwork\Modules\ExternalIssue\Models\ExternalIssueFile;
use Artwork\Modules\ExternalIssue\Services\ExternalIssueService;
use Artwork\Modules\InternalIssue\Models\InternalIssueFile;
use Artwork\Modules\Inventory\Models\InventoryArticle;
use Artwork\Modules\Inventory\Services\InventoryUserFilterShareService;
use Artwork\Modules\MaterialSet\Models\MaterialSet;
use Artwork\Modules\Permission\Enums\PermissionEnum;
use Artwork\Modules\User\Models\User;
use Barryvdh\Snappy\Facades\SnappyPdf;
use Illuminate\Auth\AuthManager;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\HeaderUtils;

class ExternalIssueController extends Controller
{
    public function __construct(
        protected ExternalIssueService $externalIssueService,
        protected AuthManager $auth,
        protected InventoryUserFilterShareService $inventoryUserFilterShareService,
    ) {}

    /**
     * Namenssuche über externe Materialausgaben als Kopierquelle: liefert die
     * Artikel (inkl. pivot.quantity) mit, damit das Frontend sie übernehmen kann.
     */
    public function searchForCopy(): JsonResponse
    {
        $this->authorize('viewAny', ExternalIssue::class);

        $q = trim((string) request()->input('q', ''));
        $excludeId = (int) request()->input('exclude_id', 0);

        $issues = ExternalIssue::query()
            ->with([
                'articles.images',
                'articles.category',
                'articles.subCategory',
            ])
            ->when($excludeId > 0, fn ($builder) => $builder->where('id', '!=', $excludeId))
            ->when($q !== '', fn ($builder) => $builder->where('name', 'like', "%{$q}%"))
            ->orderByDesc('created_at')
            ->limit(15)
            ->get();

        return response()->json($issues);
    }

    public function index()
    {
        $this->authorize('viewAny', ExternalIssue::class);

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
        $projectId   = request()?->filled('project_id')      ? (int) request()->input('project_id')      : null;
        $overdueOnly = request()?->boolean('overdue_only');

        $q        = trim((string) request()?->input('q', ''));

        $issuesQuery = ExternalIssue::with([
            'files',
            'articles', 'articles.images',
            'specialItems',
            'issuedBy',
            'receivedBy',
            'project',
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
            ->when($receivedBy !== null, fn($q) => $q->where('received_by_id', $receivedBy))
            ->when($projectId !== null,  fn($q) => $q->where('project_id',     $projectId))
            ->when($overdueOnly, fn($q) => $q
                ->whereNull('received_by_id')
                ->whereDate('return_date', '<', now()->toDateString()));

        // Name/Extern/Remarks Suche
        if ($q !== '') {
            $issuesQuery->where(function ($sub) use ($q) {
                $sub->where('name', 'like', "%{$q}%")
                    ->orWhere('external_name', 'like', "%{$q}%")
                    ->orWhere('return_remarks', 'like', "%{$q}%");
            });
        }

        $issuesQuery
            ->orderBy('issue_date')
            ->orderBy('return_date');

        // Deep-Link aus der Rückgabe-Erinnerung (?issue=): auf die Seite
        // springen, auf der die Ausgabe liegt — sonst läuft der Link auf
        // Seite 1 ins Leere. Frontend hebt die Zeile anhand des Params hervor.
        $page = null;
        $highlightIssueId = request()?->filled('issue') ? (int) request()->input('issue') : null;
        if ($highlightIssueId !== null && !request()->filled('page')) {
            $position = (clone $issuesQuery)->pluck('id')->search($highlightIssueId);
            if ($position !== false) {
                $page = intdiv($position, max($entitiesPerPage, 1)) + 1;
            }
        }

        $issues = $issuesQuery->paginate($entitiesPerPage, ['*'], 'page', $page);

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
                'article_ids','date_from','date_to','issued_by_id','received_by_id','project_id','overdue_only','q','issue'
            ]),
        ]);
    }


    public function store(StoreExternalIssueRequest $request): \Illuminate\Http\RedirectResponse
    {
        $this->authorize('create', [ExternalIssue::class, $request->integer('project_id') ?: null]);

        $issue = $this->externalIssueService->store($request->validated(), $request->file('files', []));
        return redirect()->route('extern-issue-of-material.index');
    }

    public function update(UpdateExternalIssueRequest $request, ExternalIssue $externalIssue): \Illuminate\Http\RedirectResponse
    {
        $this->authorize('update', $externalIssue);

        $issue = $this->externalIssueService->update($externalIssue, $request->validated(), $request->file('files', []));

        return redirect()->route('extern-issue-of-material.index');
    }

    public function destroy(ExternalIssue $externalIssue): \Illuminate\Http\RedirectResponse
    {
        $this->authorize('delete', $externalIssue);

        $this->externalIssueService->delete($externalIssue);

        return redirect()->route('extern-issue-of-material.index');
    }

    /**
     * Rückgabe bestätigen — aus der Übersicht wie aus der Benachrichtigung.
     * redirect()->back(), damit die Notification-Seite nicht verlassen wird.
     */
    public function returnExternal(ExternalIssue $externalIssue, Request $request): \Illuminate\Http\RedirectResponse
    {
        $this->authorizeReturnHandling($externalIssue);

        $validated = $request->validate([
            'return_remarks' => ['nullable', 'string'],
        ]);

        $this->externalIssueService->confirmReturn(
            $externalIssue,
            $validated['return_remarks'] ?? null,
            $request->exists('return_remarks')
        );

        return redirect()->back();
    }

    public function declineReturn(ExternalIssue $externalIssue): \Illuminate\Http\RedirectResponse
    {
        $this->authorizeReturnHandling($externalIssue);

        $this->externalIssueService->declineReturn($externalIssue);

        return redirect()->back();
    }

    /**
     * Rückmeldungen dürfen nur Disponent*innen oder die verantwortliche Person
     * der Ausgabe geben — sonst kann jede*r Angemeldete fremde Rückgabestatus
     * umstellen und die Benachrichtigungen anderer manipulieren.
     */
    protected function authorizeReturnHandling(ExternalIssue $externalIssue): void
    {
        $user = $this->auth->user();

        abort_unless(
            $user !== null
            && ($user->can(PermissionEnum::INVENTORY_DISPOSITION->value)
                || $externalIssue->issued_by_id === $user->id),
            403
        );
    }

    public function setSpecialItemsDone(ExternalIssue $externalIssue): \Illuminate\Http\RedirectResponse
    {
        $this->authorize('update', $externalIssue);

        $externalIssue->update(['special_items_done' => true]);

        return redirect()->route('extern-issue-of-material.index');
    }

    public function print(ExternalIssue $externalIssue)
    {
        $this->authorize('view', $externalIssue);

        $externalIssue->load(['articles.category', 'articles.subCategory', 'specialItems.category', 'specialItems.subCategory', 'files', 'issuedBy', 'receivedBy']);

        $createdAt = now()->format('d.m.Y');
        $createdBy = $this->auth->user()->full_name;

        $pdf = SnappyPdf::loadView('pdf.external_issue', [
            'issue' => $externalIssue,
            'createdAt' => $createdAt,
            'createdBy' => $createdBy,
        ]);

        $pdfContent = $pdf->output();
        $fileName = 'ext._Materialausgabe_Nr._' . $externalIssue->id . '_' . now()->format('Y-m-d') . '.pdf';

        // Vorschau-Modus (Abnahme MAT-03 Ref. 1.14): PDF nur anzeigen, NICHT speichern
        // und NICHT als Datei an die Ausgabe hängen — das passiert erst beim finalen Erstellen
        if (!request()->boolean('preview')) {
            $storagePath = 'external_material_issues/'
                . StoredFileName::forGenerated('pdf', (string) $externalIssue->id);

            Storage::disk('public')->put($storagePath, $pdfContent);

            ExternalIssueFile::create([
                'external_issue_id' => $externalIssue->id,
                'file_path' => $storagePath,
                'original_name' => $fileName,
            ]);
        }

        return response($pdfContent, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => HeaderUtils::makeDisposition(
                HeaderUtils::DISPOSITION_INLINE,
                $fileName,
                'materialausgabe.pdf'
            ),
        ]);
    }

    public function fileDelete(ExternalIssueFile $externalIssueFile): \Illuminate\Http\JsonResponse
    {
        $this->externalIssueService
            ->deleteFile($externalIssueFile);

        return response()->json(['message' => 'File deleted successfully'], 200);
    }
}
