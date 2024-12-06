<?php

namespace Artwork\Modules\Event\Http\Controllers;

use App\Http\Controllers\Controller;
use Artwork\Modules\Event\Http\Requests\EventListOrCalendarExportFilterCacheRequest;
use Artwork\Modules\Event\Services\EventCalendarExportService;
use Artwork\Modules\Event\Services\EventListExportService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Redirector;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Throwable;

class EventListOrCalendarExportController extends Controller
{
    public function __construct(
        private readonly Redirector $redirector,
        private readonly LoggerInterface $logger,
        private readonly EventListExportService $eventListXlsxExportService,
        private readonly EventCalendarExportService $calendarExportService
    ) {
    }

    /**
     * @throws Throwable
     */
    public function cacheExportConfiguration(EventListOrCalendarExportFilterCacheRequest $request): string
    {
        try {
            return $this->eventListXlsxExportService->cacheExportConfiguration($request->collect());
        } catch (Throwable $t) {
            $this->logger->error(sprintf('Could not cache filters for reason "%s"', $t->getMessage()));

            //throw for axios for proper handling with .catch()
            throw $t;
        }
    }

    /**
     * @throws Throwable
     */
    public function downloadEventListXlsx(string $cacheToken): RedirectResponse|BinaryFileResponse
    {
        try {
            return $this->eventListXlsxExportService->downloadBy($cacheToken);
        } catch (Throwable $t) {
            $this->logger->error(sprintf('Could not create xlsx export for reason "%s"', $t->getMessage()));
            //$this->logger->debug($t->getTraceAsString());

            return $this->redirector->back();
        }
    }

    /**
     * @throws Throwable
     */
    public function downloadCalendarXlsx(string $cacheToken): RedirectResponse|BinaryFileResponse
    {
        try {
            return $this->calendarExportService->downloadBy($cacheToken);
        } catch (Throwable $t) {
            $this->logger->error(sprintf('Could not create xlsx export for reason "%s"', $t->getMessage()));
            //$this->logger->debug($t->getTraceAsString());

            return $this->redirector->back();
        }
    }
}
