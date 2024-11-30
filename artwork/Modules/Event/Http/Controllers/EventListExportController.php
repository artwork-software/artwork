<?php

namespace Artwork\Modules\Event\Http\Controllers;

use App\Http\Controllers\Controller;
use Artwork\Modules\Event\Http\Requests\CacheEventListXlsxExportRequest;
use Artwork\Modules\Event\Services\EventListExportService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Redirector;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Throwable;

class EventListExportController extends Controller
{
    public function __construct(
        private readonly Redirector $redirector,
        private readonly LoggerInterface $logger,
        private readonly EventListExportService $eventListXlsxExportService
    ) {
    }

    /**
     * @throws Throwable
     */
    public function saveExportDataInCache(CacheEventListXlsxExportRequest $request): string
    {
        try {
            return $this->eventListXlsxExportService->cacheRequestData($request->collect());
        } catch (Throwable $t) {
            $this->logger->error(sprintf('Could not cache filters for reason "%s"', $t->getMessage()));
            //throw for axios for proper handling with .catch()
            throw $t;
        }
    }

    /**
     * @throws Throwable
     */
    public function downloadXlsx(string $cacheToken): RedirectResponse|BinaryFileResponse
    {
        [
            $filenameAddition,
            $configuredExport
        ] = $this->eventListXlsxExportService->getConfiguredExport($cacheToken);

        try {
            return $configuredExport
                ->download($this->eventListXlsxExportService->createXlsxExportFilename($filenameAddition))
                ->deleteFileAfterSend();
        } catch (Throwable $t) {
            $this->logger->error(sprintf('Could not create xlsx export for reason "%s"', $t->getMessage()));
            return $this->redirector->route('projects');
        }
    }
}
