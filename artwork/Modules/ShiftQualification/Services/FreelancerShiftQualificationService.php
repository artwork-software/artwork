<?php

namespace Artwork\Modules\ShiftQualification\Services;

use App\Models\Freelancer;
use Artwork\Modules\ShiftQualification\Http\Requests\UpdateFreelancerShiftQualificationRequest;
use Artwork\Modules\ShiftQualification\Models\FreelancerShiftQualification;
use Artwork\Modules\ShiftQualification\Repositories\FreelancerShiftQualificationRepository;

readonly class FreelancerShiftQualificationService
{
    public function __construct(private FreelancerShiftQualificationRepository $freelancerShiftQualificationRepository)
    {
    }

    public function createByRequestForFreelancer(
        UpdateFreelancerShiftQualificationRequest $request,
        Freelancer $freelancer
    ): void {
        $this->freelancerShiftQualificationRepository->save(
            new FreelancerShiftQualification(
                [
                    'shift_qualification_id' => $request->get('shiftQualificationId'),
                    'freelancer_id' => $freelancer->id
                ]
            )
        );
    }

    public function deleteByRequestForFreelancer(
        UpdateFreelancerShiftQualificationRequest $request,
        Freelancer $freelancer
    ): void {
        $this->freelancerShiftQualificationRepository->removeByFreelancerIdAndShiftQualificationId(
            $freelancer->id,
            $request->get('shiftQualificationId'),
        );
    }
}
