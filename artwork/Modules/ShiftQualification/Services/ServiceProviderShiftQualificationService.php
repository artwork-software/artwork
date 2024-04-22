<?php

namespace Artwork\Modules\ShiftQualification\Services;

use App\Models\ServiceProvider;
use Artwork\Modules\ShiftQualification\Http\Requests\UpdateServiceProviderShiftQualificationRequest;
use Artwork\Modules\ShiftQualification\Models\ServiceProviderShiftQualification;
use Artwork\Modules\ShiftQualification\Repositories\ServiceProviderShiftQualificationRepository;

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
