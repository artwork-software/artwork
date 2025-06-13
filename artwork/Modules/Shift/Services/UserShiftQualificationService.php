<?php

namespace Artwork\Modules\Shift\Services;

use Artwork\Modules\Shift\Http\Requests\UpdateUserShiftQualificationRequest;
use Artwork\Modules\Shift\Models\UserShiftQualification;
use Artwork\Modules\Shift\Repositories\UserShiftQualificationRepository;
use Artwork\Modules\User\Models\User;

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
