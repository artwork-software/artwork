<?php

namespace Artwork\Modules\Shift\Services;

use Artwork\Modules\ServiceProvider\Models\ServiceProvider;
use Artwork\Modules\Shift\Http\Requests\UpdateServiceProviderShiftQualificationRequest;
use Artwork\Modules\Shift\Models\ServiceProviderShiftQualification;
use Artwork\Modules\Shift\Repositories\ServiceProviderShiftQualificationRepository;

readonly class ServiceProviderShiftQualificationService
{
    public function __construct(
        private ServiceProviderShiftQualificationRepository $serviceProviderShiftQualificationRepository
    ) {
    }

    public function createByRequestForServiceProvider(
        UpdateServiceProviderShiftQualificationRequest $request,
        ServiceProvider $serviceProvider
    ): void {
        $this->serviceProviderShiftQualificationRepository->save(
            new ServiceProviderShiftQualification(
                [
                    'shift_qualification_id' => $request->get('shiftQualificationId'),
                    'service_provider_id' => $serviceProvider->id
                ]
            )
        );
    }

    public function deleteByRequestForServiceProvider(
        UpdateServiceProviderShiftQualificationRequest $request,
        ServiceProvider $serviceProvider
    ): void {
        $this->serviceProviderShiftQualificationRepository->removeByServiceProviderIdAndShiftQualificationId(
            $serviceProvider->id,
            $request->get('shiftQualificationId'),
        );
    }
}
