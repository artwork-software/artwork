<?php

namespace Artwork\Modules\ExternalIssue\Http\Controllers;

use App\Http\Controllers\Controller;
use Artwork\Core\FileHandling\Download\PrivateFileResponse;
use Artwork\Core\FileHandling\Naming\StoredFileName;
use Artwork\Modules\ExternalIssue\Exports\ExternalIssueListExport;
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
use Artwork\Modules\Project\Services\ProjectTabService;
use Artwork\Modules\User\Models\User;
use Barryvdh\Snappy\Facades\SnappyPdf;
use Illuminate\Auth\AuthManager;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\HeaderUtils;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExternalIssueController extends Controller
{
    public function __construct(
        protected ExternalIssueService $externalIssueService,
        protected AuthManager $auth,
        protected InventoryUserFilterShareService $inventoryUserFilterShareService,
        protected ProjectTabService $projectTabService,
    ) {
    }

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
                // articles() schließt Papierkorb-Artikel ein (Historie); kopieren lassen sie sich nicht
                'articles' => fn ($articles) => $articles->withoutTrashed(),
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
        $articleIds = $this->requestedIds('article_ids');

        $issuesQuery = $this->filteredIssuesQuery()->with([
            'files',
            'articles', 'articles.images',
            // $appends room/manufacturer/category/subCategory – sonst drei Abfragen je Artikel
            'articles.properties', 'articles.category', 'articles.subCategory',
            'specialItems',
            'issuedBy',
            'receivedBy',
            'project',
        ]);

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
                ? InventoryArticle::with(['properties', 'category', 'subCategory'])->whereIn('id', $articleIds)->get()
                : [],
            'materialSets' => MaterialSet::with(
                'items.article',
                'items.article.category',
                'items.article.subCategory',
                'items.article.properties'
            )->get(),
            'detailedArticle' => Inertia::optional(fn () =>
            InventoryArticle::with([
                'category',
                'subCategory',
                'properties',
                'images' => fn ($q) => $q->orderBy('is_main_image', 'desc')->orderBy('id'),
                'statusValues',
                'detailedArticleQuantities.status',
            ])->find(request()?->get('articleId'))),
            // optional, falls du urlParameters nutzt:
            'first_project_tab_id' => $this->projectTabService->getFirstProjectTabId(),
            'urlParameters' => request()->only([
                'article_ids', 'date_from', 'date_to', 'issued_by_id', 'received_by_id',
                'project_id', 'overdue_only', 'q', 'issue', 'return_state', 'special_open', 'sort', 'direction',
            ]),
        ]);
    }

    /**
     * Spalten, nach denen die Liste sortiert werden darf (Parameter `sort`).
     */
    private const SORTABLE_COLUMNS = ['name', 'issue_date', 'return_date', 'material_value', 'external_name'];

    /**
     * Werte des Rückgabe-Filters (Parameter `return_state`).
     */
    private const RETURN_STATES = ['open', 'overdue', 'returned'];

    /**
     * IDs aus einem Parameter, der als Liste oder kommagetrennt ankommt.
     *
     * @return array<int, int>
     */
    private function requestedIds(string $key): array
    {
        $input = request()->input($key, '');
        $values = is_array($input) ? $input : (is_string($input) && $input !== '' ? explode(',', $input) : []);

        return array_values(array_unique(array_filter(array_map('intval', $values))));
    }

    /**
     * Gefilterte und sortierte Ausgaben – gemeinsame Grundlage für Liste und Excel-Export, damit
     * beide dieselben Filter anwenden.
     *
     * @return Builder<ExternalIssue>
     */
    private function filteredIssuesQuery(): Builder
    {
        $articleIds = $this->requestedIds('article_ids');
        $selectedIds = $this->requestedIds('ids');
        $dateFrom = request()->input('date_from');
        $dateTo   = request()->input('date_to');
        $issuedBy    = request()->filled('issued_by_id')    ? (int) request()->input('issued_by_id')    : null;
        $receivedBy  = request()->filled('received_by_id')  ? (int) request()->input('received_by_id')  : null;
        $projectId   = request()->filled('project_id')      ? (int) request()->input('project_id')      : null;
        // "Nur überfällige" (alter Parameter, auch im Deep-Link der Erinnerung) = Rückgabe-Filter "overdue"
        $returnState = request()->boolean('overdue_only') ? 'overdue' : request()->input('return_state');
        $returnState = in_array($returnState, self::RETURN_STATES, true) ? $returnState : null;
        $q = trim((string) request()->input('q', ''));
        $sort = in_array(request()->input('sort'), self::SORTABLE_COLUMNS, true) ? request()->input('sort') : null;
        $direction = request()->input('direction') === 'desc' ? 'desc' : 'asc';
        $today = now()->toDateString();

        return ExternalIssue::query()
            ->when($selectedIds !== [], fn ($query) => $query->whereIn('external_issues.id', $selectedIds))
            ->when($articleIds !== [], function ($query) use ($articleIds): void {
                $query->whereHas('articles', function ($sub) use ($articleIds): void {
                    $sub->whereIn('inventory_articles.id', $articleIds);
                });
            })
            // Zeitfilter: entweder im Ausgabedatum ODER im Rückgabedatum innerhalb Range
            ->overlapping($dateFrom, $dateTo)
            ->when($issuedBy !== null, fn ($query) => $query->where('issued_by_id', $issuedBy))
            ->when($receivedBy !== null, fn ($query) => $query->where('received_by_id', $receivedBy))
            ->when($projectId !== null, fn ($query) => $query->where('project_id', $projectId))
            // Rückgabe: gleiche Regel wie Verfügbarkeit/Abzeichen (Status, „Erhalten von“, Altbestand)
            ->when($returnState === 'overdue', fn ($query) => $query
                ->notReturned()
                ->whereDate('return_date', '<', $today))
            ->when($returnState === 'open', fn ($query) => $query
                ->notReturned()
                ->where(function ($open) use ($today): void {
                    $open->whereNull('return_date')->orWhereDate('return_date', '>=', $today);
                }))
            ->when($returnState === 'returned', fn ($query) => $query
                ->whereNotIn('external_issues.id', ExternalIssue::query()->notReturned()->select('external_issues.id')))
            // Nur Ausgaben mit noch offenen Sonderartikeln
            ->when(request()->boolean('special_open'), fn ($query) => $query
                ->where('special_items_done', false)
                ->whereHas('specialItems'))
            ->when($q !== '', function ($query) use ($q): void {
                $query->where(function ($sub) use ($q): void {
                    $sub->where('name', 'like', "%{$q}%")
                        ->orWhere('external_name', 'like', "%{$q}%")
                        ->orWhere('return_remarks', 'like', "%{$q}%");
                });
            })
            ->when(
                $sort !== null,
                fn ($query) => $query->orderBy($sort, $direction)->orderBy('id'),
                fn ($query) => $query->orderBy('issue_date')->orderBy('return_date')
            );
    }

    /**
     * Excel-Export der Liste mit denselben Filtern wie die Übersicht (mit `ids` nur die Auswahl).
     */
    public function export(): BinaryFileResponse
    {
        $this->authorize('viewAny', ExternalIssue::class);

        $issues = $this->filteredIssuesQuery()
            ->with(['issuedBy', 'receivedBy', 'project', 'articles', 'specialItems', 'files'])
            ->get();

        return (new ExternalIssueListExport($issues))
            ->download('externe-materialausgaben-' . now()->format('Y-m-d') . '.xlsx');
    }

    /**
     * Löscht mehrere Ausgaben auf einmal. Fehlt für eine davon das Recht, wird keine gelöscht.
     */
    public function bulkDestroy(Request $request): JsonResponse
    {
        // Sammelaktionen gehören zur Liste: erst das Recht für die Liste, dann die Eingabe prüfen
        $this->authorize('viewAny', ExternalIssue::class);

        $validated = $request->validate([
            'ids' => ['required', 'array', 'min:1', 'max:200'],
            'ids.*' => ['integer', 'distinct'],
        ]);

        $issues = ExternalIssue::query()->whereIn('id', $validated['ids'])->get();
        abort_unless($issues->count() === count($validated['ids']), 404);
        $issues->each(fn (ExternalIssue $issue) => $this->authorize('delete', $issue));

        // alles oder nichts: scheitert eine Ausgabe, bleibt keine gelöscht
        DB::transaction(fn () => $issues->each(fn (ExternalIssue $issue) => $this->externalIssueService->delete($issue)));

        return response()->json(['deleted' => $issues->count()]);
    }


    public function store(StoreExternalIssueRequest $request): \Illuminate\Http\RedirectResponse
    {
        $this->authorize('create', [ExternalIssue::class, $request->integer('project_id') ?: null]);

        $issue = $this->externalIssueService->store($request->validated(), $request->file('files', []));
        return redirect()->route('extern-issue-of-material.index');
    }

    public function update(
        UpdateExternalIssueRequest $request,
        ExternalIssue $externalIssue
    ): \Illuminate\Http\RedirectResponse {
        $this->authorize('update', $externalIssue);

        $issue = $this->externalIssueService->update(
            $externalIssue,
            $request->validated(),
            $request->file('files', [])
        );

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

    /**
     * Druckansicht mehrerer Ausgaben in einer PDF-Datei. Reine Ansicht: anders als der Einzeldruck
     * wird nichts gespeichert oder an die Ausgaben gehängt.
     */
    public function printMany(): \Illuminate\Http\Response
    {
        $this->authorize('viewAny', ExternalIssue::class);

        $ids = $this->requestedIds('ids');
        abort_if($ids === [] || count($ids) > 50, 422);

        $issues = ExternalIssue::query()
            ->whereIn('id', $ids)
            ->with([
                'articles.category', 'articles.subCategory', 'specialItems.category', 'specialItems.subCategory',
                'files', 'issuedBy', 'receivedBy',
            ])
            ->orderBy('issue_date')
            ->orderBy('return_date')
            ->get();
        abort_unless($issues->count() === count($ids), 404);
        $issues->each(fn (ExternalIssue $issue) => $this->authorize('view', $issue));

        $createdAt = now()->format('d.m.Y');
        $createdBy = $this->auth->user()->full_name;
        // Je Ausgabe ein eigenes Dokument: Snappy fügt mehrere Eingaben zu einer PDF zusammen
        $documents = $issues->map(fn (ExternalIssue $issue): string => view('pdf.external_issue', [
            'issue' => $issue,
            'createdAt' => $createdAt,
            'createdBy' => $createdBy,
        ])->render())->all();

        return response(SnappyPdf::loadHTML($documents)->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => HeaderUtils::makeDisposition(
                HeaderUtils::DISPOSITION_INLINE,
                'externe-materialausgaben-' . now()->format('Y-m-d') . '.pdf'
            ),
        ]);
    }

    public function print(ExternalIssue $externalIssue)
    {
        $this->authorize('view', $externalIssue);

        $externalIssue->load([
            'articles.category',
            'articles.subCategory',
            'specialItems.category',
            'specialItems.subCategory',
            'files',
            'issuedBy',
            'receivedBy',
        ]);

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

            Storage::disk('local')->put($storagePath, $pdfContent);

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

    /**
     * Nur mit MaterialIssuePolicy::view und nur für Dateien dieser Ausgabe (?inline=1 für Bilder/PDF).
     */
    public function fileDownload(
        Request $request,
        ExternalIssue $externalIssue,
        ExternalIssueFile $externalIssueFile
    ): StreamedResponse {
        $this->authorize('view', $externalIssue);
        abort_unless((int) $externalIssueFile->external_issue_id === (int) $externalIssue->id, 404);

        return PrivateFileResponse::make(
            (string) $externalIssueFile->file_path,
            $externalIssueFile->original_name,
            $request->boolean('inline')
        );
    }

    public function fileDelete(ExternalIssueFile $externalIssueFile): \Illuminate\Http\JsonResponse
    {
        $this->externalIssueService
            ->deleteFile($externalIssueFile);

        return response()->json(['message' => 'File deleted successfully'], 200);
    }
}
