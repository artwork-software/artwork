<?php

namespace Artwork\Modules\InternalIssue\Http\Controllers;

use App\Http\Controllers\Controller;
use Artwork\Core\FileHandling\Download\PrivateFileResponse;
use Artwork\Core\FileHandling\Naming\DownloadFileName;
use Artwork\Core\FileHandling\Naming\StoredFileName;
use Artwork\Modules\InternalIssue\Exports\InternalIssueListExport;
use Artwork\Modules\InternalIssue\Http\Requests\StoreInternalIssueRequest;
use Artwork\Modules\InternalIssue\Http\Requests\UpdateInternalIssueRequest;
use Artwork\Modules\InternalIssue\Models\InternalIssue;
use Artwork\Modules\InternalIssue\Models\InternalIssueFile;
use Artwork\Modules\InternalIssue\Services\InternalIssueService;
use Artwork\Modules\Inventory\Models\InventoryArticle;
use Artwork\Modules\Inventory\Services\InventoryUserFilterService;
use Artwork\Modules\Inventory\Services\InventoryUserFilterShareService;
use Artwork\Modules\MaterialSet\Models\MaterialSet;
use Artwork\Modules\Project\Services\ProjectTabService;
use Artwork\Modules\User\Models\User;
use Barryvdh\Snappy\Facades\SnappyPdf;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Illuminate\Auth\AuthManager;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\HeaderUtils;
use Symfony\Component\HttpFoundation\StreamedResponse;

class InternalIssueController extends Controller
{
    public function __construct(
        protected InternalIssueService $internalIssueService,
        protected InventoryUserFilterShareService $inventoryUserFilterShareService,
        protected AuthManager $authManger,
        protected InventoryUserFilterService $inventoryUserFilterService,
        protected ProjectTabService $projectTabService
    ) {
    }

    public function index(): \Inertia\Response
    {
        $this->authorize('viewAny', InternalIssue::class);

        $entitiesPerPage = min(max(request()->integer('entitiesPerPage', 10), 1), 100);
        $articleIds = $this->requestedIds('article_ids');

        $issuesQuery = $this->filteredIssuesQuery()
            ->with([
                'files',
                'articles.images',
                'articles.category',
                'articles.subCategory',
                // $appends room/manufacturer lesen properties – sonst eine Abfrage je Artikel
                'articles.properties',
                'specialItems',
                // Liste und Bearbeiten-Dialog lesen vom Raum nur id und Namen — ohne Room::$with
                // (Admins, Ersteller als volle User-Modelle)
                'room' => fn ($query) => $query->without(['admins', 'creator']),
                'project',
                'responsibleUsers',
            ]);

        $issues = $issuesQuery->paginate($entitiesPerPage);
        // Seite hinter dem Ende (etwa nach dem Löschen der letzten Einträge): letzte vorhandene Seite zeigen
        if ($issues->isEmpty() && $issues->total() > 0 && $issues->currentPage() > $issues->lastPage()) {
            $issues = $issuesQuery->paginate($entitiesPerPage, ['*'], 'page', $issues->lastPage());
        }
        $issues->withQueryString();
        // Verantwortliche nur als Avatar + Tooltip (Kontaktdaten lädt der Tooltip per lazy-load nach)
        $issues->getCollection()->each(
            fn (InternalIssue $issue) => $issue->responsibleUsers->each->setVisible(User::AVATAR_FIELDS)
        );

        $articlesInFilter = !empty($articleIds)
            ? InventoryArticle::with(['properties', 'category', 'subCategory'])->whereIn('id', $articleIds)->get()
            : collect([]);

        // ⚠️ Tippfehler korrigiert: authManager (nicht authManger)
        $this->inventoryUserFilterShareService->getFilterDataForUser($this->authManger->user());

        return Inertia::render('IssueOfMaterial/IssueOfMaterialManagement', [
            'issues' => $issues,
            'articlesInFilter' => $articlesInFilter,
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
                'images' => function ($query): void {
                    $query->orderBy('is_main_image', 'desc')->orderBy('id');
                },
                'statusValues',
                'detailedArticleQuantities.status',
            ])->find(request()->get('articleId'))),
            'first_project_tab_id' => $this->projectTabService->getFirstProjectTabId(),
            'urlParameters' => request()->only([
                'article_ids','date_from','date_to','project_id','room_id','responsible_user_ids','q',
                'special_open','sort','direction',
            ]),
        ]);
    }

    /**
     * Spalten, nach denen die Liste sortiert werden darf (Parameter `sort`).
     */
    private const SORTABLE_COLUMNS = ['name', 'start_date', 'end_date'];

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
     * @return Builder<InternalIssue>
     */
    private function filteredIssuesQuery(): Builder
    {
        $q = trim($this->stringParameter('q') ?? '');
        $articleIds = $this->requestedIds('article_ids');
        $responsibleUserIds = $this->requestedIds('responsible_user_ids');
        $selectedIds = $this->requestedIds('ids');
        $dateFrom  = $this->stringParameter('date_from'); // Y-m-d
        $dateTo    = $this->stringParameter('date_to');   // Y-m-d
        $projectId = (int) request()->input('project_id', 0);
        $roomId    = (int) request()->input('room_id', 0);
        $sort = in_array(request()->input('sort'), self::SORTABLE_COLUMNS, true) ? request()->input('sort') : null;
        $direction = request()->input('direction') === 'desc' ? 'desc' : 'asc';

        return InternalIssue::query()
            ->when($selectedIds !== [], fn ($query) => $query->whereIn('internal_issues.id', $selectedIds))
            ->when($articleIds !== [], function ($query) use ($articleIds): void {
                $query->whereHas('articles', function ($sub) use ($articleIds): void {
                    $sub->whereIn('inventory_articles.id', $articleIds);
                });
            })
            ->when($projectId > 0, fn ($query) => $query->where('project_id', $projectId))
            ->when($roomId > 0, fn ($query) => $query->where('room_id', $roomId))
            ->when($responsibleUserIds !== [], function ($query) use ($responsibleUserIds): void {
                $query->whereHas('responsibleUsers', function ($sub) use ($responsibleUserIds): void {
                    $sub->whereIn('users.id', $responsibleUserIds);
                });
            })
            // Nur Ausgaben mit noch offenen Sonderartikeln (dieselbe Regel wie das Status-Abzeichen)
            ->when(request()->boolean('special_open'), fn ($query) => $query
                ->where('special_items_done', false)
                ->whereHas('specialItems'))
            // Zeitraum-Overlap statt Startdatum-AND-Filter
            ->overlapping($dateFrom, $dateTo)
            ->when($q !== '', function ($query) use ($q): void {
                $query->where(function ($sub) use ($q): void {
                    $sub->where('name', 'like', "%{$q}%")
                        ->orWhere('notes', 'like', "%{$q}%");
                });
            })
            ->when(
                $sort !== null,
                fn ($query) => $query->orderBy($sort, $direction)->orderBy('id'),
                fn ($query) => $query->orderBy('start_date')->orderBy('start_time')
            );
    }

    /**
     * Excel-Export der Liste mit denselben Filtern wie die Übersicht (mit `ids` nur die Auswahl).
     */
    public function export(): BinaryFileResponse
    {
        $this->authorize('viewAny', InternalIssue::class);

        $issues = $this->filteredIssuesQuery()
            ->with(['room' => fn ($query) => $query->without(['admins', 'creator']), 'project', 'responsibleUsers',
                'articles', 'specialItems', 'files'])
            ->get();

        return (new InternalIssueListExport($issues))
            ->download('materialausgaben-' . now()->format('Y-m-d') . '.xlsx');
    }

    /**
     * Löscht mehrere Ausgaben auf einmal. Fehlt für eine davon das Recht, wird keine gelöscht.
     */
    public function bulkDestroy(Request $request): JsonResponse
    {
        // Sammelaktionen gehören zur Liste: erst das Recht für die Liste, dann die Eingabe prüfen
        $this->authorize('viewAny', InternalIssue::class);

        $validated = $request->validate([
            'ids' => ['required', 'array', 'min:1', 'max:200'],
            'ids.*' => ['integer', 'distinct'],
        ]);

        $issues = InternalIssue::query()->whereIn('id', $validated['ids'])->get();
        abort_unless($issues->count() === count($validated['ids']), 404);
        $issues->each(fn (InternalIssue $issue) => $this->authorize('delete', $issue));

        // alles oder nichts: scheitert eine Ausgabe, bleibt keine gelöscht
        DB::transaction(
            fn () => $issues->each(fn (InternalIssue $issue) => $this->internalIssueService->delete($issue))
        );

        return response()->json(['deleted' => $issues->count()]);
    }

    /**
     * Druckansicht mehrerer Ausgaben in einer PDF-Datei. Reine Ansicht: anders als der Einzeldruck
     * wird nichts gespeichert oder an die Ausgaben gehängt.
     */
    public function printMany(Request $request): \Illuminate\Http\Response
    {
        $this->authorize('viewAny', InternalIssue::class);

        $ids = $this->requestedIds('ids');
        abort_if($ids === [] || count($ids) > 50, 422);

        $issues = InternalIssue::query()
            ->whereIn('id', $ids)
            ->with([
                'articles.category', 'articles.subCategory', 'specialItems.category', 'specialItems.subCategory',
                'files', 'project', 'room', 'responsibleUsers',
            ])
            ->orderBy('start_date')
            ->orderBy('start_time')
            ->get();
        abort_unless($issues->count() === count($ids), 404);
        $issues->each(fn (InternalIssue $issue) => $this->authorize('view', $issue));

        $createdAt = now()->format('d.m.Y');
        $createdBy = $this->authManger->user()->full_name;
        // Je Ausgabe ein eigenes Dokument: Snappy fügt mehrere Eingaben zu einer PDF zusammen
        $documents = $issues->map(fn (InternalIssue $issue): string => view('pdf.internal_issue', [
            'issue' => $issue,
            'createdAt' => $createdAt,
            'createdBy' => $createdBy,
        ])->render())->all();

        return response(SnappyPdf::loadHTML($documents)->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => HeaderUtils::makeDisposition(
                HeaderUtils::DISPOSITION_INLINE,
                'materialausgaben-' . now()->format('Y-m-d') . '.pdf'
            ),
        ]);
    }


    /**
     * Namenssuche über Materialausgaben als Kopierquelle: liefert die Artikel
     * (inkl. pivot.quantity) mit, damit das Frontend sie direkt übernehmen kann.
     */
    public function searchForCopy(): JsonResponse
    {
        $this->authorize('viewAny', InternalIssue::class);

        $q = trim($this->stringParameter('q') ?? '');
        $excludeId = (int) request()->input('exclude_id', 0);

        $issues = InternalIssue::query()
            ->with([
                // articles() schließt Papierkorb-Artikel ein (Historie); kopieren lassen sie sich nicht
                'articles' => fn ($articles) => $articles->withoutTrashed(),
                'articles.images',
                'articles.category',
                'articles.subCategory',
                'project:id,name',
                'room:id,name',
            ])
            ->when($excludeId > 0, fn ($builder) => $builder->where('id', '!=', $excludeId))
            ->when($q !== '', fn ($builder) => $builder->where('name', 'like', "%{$q}%"))
            ->orderByDesc('created_at')
            ->limit(15)
            ->get();

        return response()->json($issues);
    }

    public function show(InternalIssue $internalIssue): JsonResponse
    {
        $this->authorize('view', $internalIssue);

        $internalIssue->load([
            'files',
            'articles.images',
            'articles.category',
            'articles.subCategory',
            'specialItems',
            'room',
            'project',
            'responsibleUsers.departments',
        ]);

        return response()->json($internalIssue);
    }

    public function store(StoreInternalIssueRequest $request): \Illuminate\Http\RedirectResponse
    {
        $this->authorize('create', [InternalIssue::class, $request->integer('project_id') ?: null]);

        $issue = $this->internalIssueService
            ->store($request->validated(), $request->file('files', []));

        return redirect()->back();
    }

    public function update(
        UpdateInternalIssueRequest $request,
        InternalIssue $internalIssue
    ): \Illuminate\Http\RedirectResponse {
        $this->authorize('update', $internalIssue);

        //dd($request->all());
        $issue = $this->internalIssueService
            ->update($internalIssue, $request->validated(), $request->file('files', []));

        return redirect()->back();
    }

    public function destroy(InternalIssue $internalIssue): \Illuminate\Http\RedirectResponse
    {
        $this->authorize('delete', $internalIssue);

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

    public function print(InternalIssue $internalIssue)
    {
        $this->authorize('view', $internalIssue);

        $internalIssue->load([
            'articles.category',
            'articles.subCategory',
            'specialItems.category',
            'specialItems.subCategory',
            'files',
            'project',
            'room',
            'responsibleUsers',
        ]);

        $createdAt = now()->format('d.m.Y');
        $createdBy = $this->authManger->user()->full_name;

        $pdf = SnappyPdf::loadView('pdf.internal_issue', [
            'issue' => $internalIssue,
            'createdAt' => $createdAt,
            'createdBy' => $createdBy,
        ]);

        $pdfContent = $pdf->output();
        $projectPart = $internalIssue->project ? str_replace(' ', '_', $internalIssue->project->name) . '_' : '';
        // Readable name only - the project name would otherwise reach the path.
        // makeDisposition() throws on "/", "\" and "%" — a project name like
        // "Spielzeit 24/25" must not turn the print into a 500.
        $fileName = DownloadFileName::sanitize(
            'int._Materialausgabe_' . $projectPart . 'Nr._' . $internalIssue->id
            . '_' . now()->format('Y-m-d') . '.pdf'
        ) ?? 'materialausgabe.pdf';

        // Vorschau-Modus (Abnahme MAT-03 Ref. 1.14): PDF nur anzeigen, NICHT speichern
        // und NICHT als Datei an die Ausgabe hängen — das passiert erst beim finalen Erstellen
        if (!request()->boolean('preview')) {
            $storagePath = 'material-issue/' . StoredFileName::forGenerated('pdf', (string) $internalIssue->id);

            Storage::disk('local')->put($storagePath, $pdfContent);

            InternalIssueFile::create([
                'internal_issue_id' => $internalIssue->id,
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
        InternalIssue $internalIssue,
        InternalIssueFile $internalIssueFile
    ): StreamedResponse {
        $this->authorize('view', $internalIssue);
        abort_unless((int) $internalIssueFile->internal_issue_id === (int) $internalIssue->id, 404);

        return PrivateFileResponse::make(
            (string) $internalIssueFile->file_path,
            $internalIssueFile->original_name,
            $request->boolean('inline')
        );
    }

    public function setSpecialItemsDone(InternalIssue $internalIssue): \Illuminate\Http\RedirectResponse
    {
        $this->authorize('update', $internalIssue);

        $internalIssue->update(['special_items_done' => true]);

        return redirect()->back();
    }

    /**
     * Abfrage-Parameter nur als Text: Felder (?q[]=x) führten sonst zu einem Fehler statt zu "kein Filter".
     */
    private function stringParameter(string $key): ?string
    {
        $value = request()->input($key);

        return is_string($value) ? $value : null;
    }
}
