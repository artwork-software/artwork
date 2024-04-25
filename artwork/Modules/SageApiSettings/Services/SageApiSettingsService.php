<?php

namespace Artwork\Modules\SageApiSettings\Services;

use Artwork\Modules\Sage100\Clients\Sage100Client;
use Artwork\Modules\SageApiSettings\Http\Requests\CreateOrUpdateSageApiSettingsRequest;
use Artwork\Modules\SageApiSettings\Models\SageApiSettings;
use Artwork\Modules\SageApiSettings\Repositories\SageApiSettingsRepository;
use Carbon\Carbon;
use Throwable;

readonly class SageApiSettingsService
{
    public function __construct(private SageApiSettingsRepository $sageApiSettingsRepository)
    {
    }

    public function getFirst(): SageApiSettings|null
    {
        /** @var SageApiSettings|null $sageApiSettings */
        $sageApiSettings = $this->sageApiSettingsRepository->getFirst();

        return $sageApiSettings;
    }

    /**
     * @throws Throwable
     */
    public function createOrUpdateFromRequest(
        CreateOrUpdateSageApiSettingsRequest $createOrUpdateSageApiSettingsRequest
    ): SageApiSettings {
        if (!$sageApiSettings = $this->getFirst()) {
            $sageApiSettings = new SageApiSettings($createOrUpdateSageApiSettingsRequest->all());

            $this->sageApiSettingsRepository->saveOrFail($sageApiSettings);

            return $sageApiSettings;
        }

        $this->sageApiSettingsRepository->updateOrFail($sageApiSettings, $createOrUpdateSageApiSettingsRequest->all());

        return $sageApiSettings;
    }

    public function testConnection(): bool
    {
        return app(Sage100Client::class)->testConnection();
    }

    public function updateBookingDate(Carbon $carbon): void
    {
        $this->sageApiSettingsRepository->update($this->getFirst(), ['bookingDate' => $carbon->format('Y-m-d')]);
    }
}
