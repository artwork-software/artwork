<?php

namespace Artwork\Modules\ShiftQualification\Services;

use Artwork\Modules\ShiftQualification\Http\Requests\UpdateUserShiftQualificationRequest;
use Artwork\Modules\ShiftQualification\Models\UserShiftQualification;
use Artwork\Modules\ShiftQualification\Repositories\UserShiftQualificationRepository;
use App\Models\User;

readonly class UserShiftQualificationService
{
    public function __construct(private UserShiftQualificationRepository $userShiftQualificationRepository)
    {
    }

    public function createByRequestForUser(UpdateUserShiftQualificationRequest $request, User $user): void
    {
        $this->userShiftQualificationRepository->save(
            new UserShiftQualification(
                [
                    'shift_qualification_id' => $request->get('shiftQualificationId'),
                    'user_id' => $user->id
                ]
            )
        );
    }

    public function deleteByRequestForUser(UpdateUserShiftQualificationRequest $request, User $user): void
    {
        $this->userShiftQualificationRepository->removeByUserIdAndShiftQualificationId(
            $user->id,
            $request->get('shiftQualificationId'),
        );
    }
}
