<?php

namespace Artwork\Modules\WorkTime\Services;

use Artwork\Modules\WorkTime\Models\WorkTimeChangeRequest;

class WorkTimeChangeRequestService
{
    public function createChangeRequest(array $data): \Artwork\Modules\WorkTime\Models\WorkTimeChangeRequest
    {
        return WorkTimeChangeRequest::create([
            'user_id' => $data['user_id'],
            'request_start_time' => $data['request_start_time'],
            'request_end_time' => $data['request_end_time'],
            'shift_id' => $data['shift_id'],
            'craft_id' => $data['craft_id'],
            'status' => 'pending',
            'request_comment' => $data['request_comment'],
            'requested_by' => $data['requested_by'],
        ]);
    }
}