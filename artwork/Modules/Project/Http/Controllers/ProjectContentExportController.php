<?php

namespace Artwork\Modules\Project\Http\Controllers;

use App\Http\Controllers\Controller;
use Artwork\Modules\Category\Models\Category;
use Artwork\Modules\Event\Exceptions\ExportLinkExpiredException;
use Artwork\Modules\EventType\Models\EventType;
use Artwork\Modules\Genre\Models\Genre;
use Artwork\Modules\Project\Http\Requests\ProjectContentExportRequest;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\Project\Models\ProjectState;
use Artwork\Modules\Project\Services\ContentExport\ProjectContentExportColumns;
use Artwork\Modules\Project\Services\ContentExport\ProjectContentExportService;
use Artwork\Modules\Sector\Models\Sector;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Throwable;

/**
 * Projektinhalte als Excel oder Word aus der Projektübersicht (Reiter im Export-Modal). Recht über die
 * Routen-Middleware "can export from project overview".
 */
class ProjectContentExportController extends Controller
{
    public function __construct(
        private readonly ProjectContentExportService $exportService,
        private readonly ProjectContentExportColumns $columns,
        private readonly LoggerInterface $logger,
    ) {
    }

    /**
     * Auswahllisten für Spalten und Projektauswahl.
     */
    public function options(Request $request): JsonResponse
    {
        $names = static fn ($query) => $query->orderBy('name')->get(['id', 'name']);

        return response()->json([
            'columns' => $this->columns->availableColumns($request->user()),
            'project_states' => $names(ProjectState::query()),
            'categories' => $names(Category::query()),
            'genres' => $names(Genre::query()),
            'sectors' => $names(Sector::query()),
            'event_types' => $names(EventType::query()),
            'project_groups' => $names(Project::query()->where('is_group', true)),
        ]);
    }

    /**
     * Anzahl der Projekte, die mit diesen Einstellungen exportiert würden, und ausgeblendete Spalten.
     */
    public function preview(ProjectContentExportRequest $request): JsonResponse
    {
        return response()->json($this->exportService->preview($request->user(), $request->configuration()));
    }

    /**
     * @throws Throwable
     */
    public function cache(ProjectContentExportRequest $request): string
    {
        return $this->exportService->cacheConfiguration($request->user(), $request->validated());
    }

    public function download(Request $request, string $cacheToken): BinaryFileResponse|Response
    {
        try {
            return $this->exportService->downloadBy($request->user(), $cacheToken);
        } catch (ExportLinkExpiredException) {
            return $this->plainTextResponse(
                __('The export link has expired. Please start the export again.'),
                Response::HTTP_GONE
            );
        } catch (AccessDeniedHttpException $exception) {
            throw $exception;
        } catch (Throwable $throwable) {
            $this->logger->error(sprintf('Could not create project content export: "%s"', $throwable->getMessage()));

            return $this->plainTextResponse(
                __('Export could not be created. Please try again.'),
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    /**
     * Der Download läuft in einem eigenen Tab: dort verständlichen Text statt einer leeren Seite zeigen.
     */
    private function plainTextResponse(string $message, int $status): Response
    {
        return new Response($message, $status, ['Content-Type' => 'text/plain; charset=UTF-8']);
    }
}
