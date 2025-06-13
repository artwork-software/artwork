<?php

namespace Artwork\Modules\Event\Abstracts;

use Artwork\Core\Carbon\Service\CarbonService;
use Artwork\Core\Services\CacheService;
use Artwork\Core\Services\CollectionService;
use Artwork\Modules\Event\Repositories\EventRepository;
use Artwork\Modules\Event\Services\EventSettingsService;
use Artwork\Modules\Project\Repositories\ProjectRepository;
use Illuminate\Support\Collection;
use Illuminate\Translation\Translator;
use InvalidArgumentException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Throwable;

abstract class EventExportService
{
    protected const XLSX_FILENAME_EXTENSION = '.xlsx';

    private ?Collection $cachedData = null;

    abstract protected function composeExport(): BinaryFileResponse;

    abstract protected function getPeriodExportTranslationKey(): string;

    abstract protected function getProjectExportTranslationKey(): string;

    public function __construct(
        protected readonly EventSettingsService $eventSettingsService,
        protected readonly EventRepository $eventRepository,
        protected readonly CacheService $cacheService,
        protected readonly CollectionService $collectionService,
        protected readonly CarbonService $carbonService,
        protected readonly Translator $translator,
        protected readonly ProjectRepository $projectRepository,
    ) {
    }

    /**
     * @throws Throwable
     */
    final public function downloadBy(string $cacheToken): BinaryFileResponse
    {
        $this->cachedData = $this->cacheService->getValueByToken($cacheToken);

        return static::composeExport();
    }

    /**
     * @throws InvalidArgumentException
     * @throws Throwable
     */
    final public function cacheExportConfiguration(Collection $configuration): string
    {
        return $this->cacheService->setValueAndGetCacheTokenValidForTenSeconds($configuration);
    }

    final protected function getFromCachedData(
        string $key = '*',
        mixed $default = null
    ): mixed {
        if (is_null($this->cachedData)) {
            return $this->collectionService->getSupportCollection();
        }

        if ($key === '*') {
            return $this->cachedData;
        }

        return $this->cachedData->get($key, $default);
    }

    protected function composeFilename(string $filenameExtension = self::XLSX_FILENAME_EXTENSION): string
    {
        $conditional = $this->getFromCachedData('conditional');
        $desiredDateFormat = $this->carbonService->getDesiredDateFormatFromLocale($this->translator->getLocale());
        $nowFormatted = $this->carbonService->getNow()->format($desiredDateFormat);

        return sprintf(
            "%s%s",
            $this->getFromCachedData('desiresTimespanExport') ?
                $this->translator->get(
                    static::getPeriodExportTranslationKey(),
                    [
                        $this->carbonService->formatFromString($conditional['dateStart'], $desiredDateFormat),
                        $this->carbonService->formatFromString($conditional['dateEnd'], $desiredDateFormat),
                        $nowFormatted
                    ]
                ) :
                $this->translator->get(
                    static::getProjectExportTranslationKey(),
                    [
                        $nowFormatted,
                        implode(
                            ', ',
                            array_map(
                                function (int $project): string {
                                    return $this->projectRepository->findOrFail($project)->getAttribute('name');
                                },
                                $conditional['projects']
                            ),
                        ),
                    ]
                ),
            $filenameExtension
        );
    }
}
