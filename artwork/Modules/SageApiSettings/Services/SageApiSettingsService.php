<?php

namespace Artwork\Modules\SageApiSettings\Services;

use Artwork\Modules\Sage100\Clients\SageClient;
use Artwork\Modules\SageApiSettings\Http\Requests\CreateOrUpdateSageApiSettingsRequest;
use Artwork\Modules\SageApiSettings\Models\SageApiSettings;
use Artwork\Modules\SageApiSettings\Repositories\SageApiSettingsRepository;
use Carbon\Carbon;
use Throwable;

class SageApiSettingsService
{
    public function __construct(private readonly SageApiSettingsRepository $sageApiSettingsRepository)
    {
    }

    public function getFirst(): SageApiSettings|null
    {
        /** @var SageApiSettings|null $sageApiSettings */
        $sageApiSettings = $this->sageApiSettingsRepository->getFirst();

        return $sageApiSettings;
    }

    /**
     * Sage-Schnittstelle gilt nur als aktiv, wenn sowohl die Umgebung (SAGE_API_ENABLED)
     * als auch der Schalter in den Schnittstellen-Einstellungen gesetzt sind.
     */
    public function isEnabled(): bool
    {
        if (!config('services.sage.enabled')) {
            return false;
        }

        return (bool) $this->getFirst()?->enabled;
    }

    /**
     * @throws Throwable
     */
    public function createOrUpdateFromRequest(
        CreateOrUpdateSageApiSettingsRequest $createOrUpdateSageApiSettingsRequest
    ): SageApiSettings {
        $attributes = $createOrUpdateSageApiSettingsRequest->validated();

        // Leeres Passwortfeld = gespeichertes Passwort behalten (das Formular bekommt es nie zu sehen).
        if (($attributes['password'] ?? '') === '') {
            unset($attributes['password']);
        }

        if (!$sageApiSettings = $this->getFirst()) {
            $sageApiSettings = new SageApiSettings($attributes);

            $this->sageApiSettingsRepository->saveOrFail($sageApiSettings);

            return $sageApiSettings;
        }

        $this->sageApiSettingsRepository->updateOrFail($sageApiSettings, $attributes);

        return $sageApiSettings;
    }

    /** @todo JR - remove this to different service */
    public function testConnection(): bool
    {
        return app(SageClient::class)->testConnection();
    }

    public function updateBookingDate(Carbon $carbon): void
    {
        $this->sageApiSettingsRepository->update($this->getFirst(), ['bookingDate' => $carbon->format('Y-m-d')]);
    }
}
