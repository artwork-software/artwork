<?php

namespace Artwork\Modules\SageApiSettings\Services;

use App\Sage100\Sage100;
use Artwork\Modules\SageApiSettings\Http\Requests\CreateOrUpdateSageApiSettingsRequest;
use Artwork\Modules\SageApiSettings\Models\SageApiSettings;
use Artwork\Modules\SageApiSettings\Repositories\SageApiSettingsRepository;
use Throwable;

class SageApiSettingsService
{
    public function __construct(
        private readonly SageApiSettingsRepository $sageApiSettingsRepository
    ) {
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
        return app(Sage100::class)->testConnection();
    }
}
