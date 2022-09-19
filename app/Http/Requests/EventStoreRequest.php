<?php

namespace App\Http\Requests;

use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class EventStoreRequest extends EventStoreOrUpdateRequest
{
    public function data()
    {
        return [
            'start_time' => Carbon::parse($this->get('start'))->setTimezone(config('app.timezone')),
            'end_time' => Carbon::parse($this->get('end'))->setTimezone(config('app.timezone')),
            'room_id' => $this->get('roomId'),
            'name' => $this->get('title'),
            'description' => $this->get('description'),
            'audience' => $this->get('audience'),
            'is_loud' => $this->get('isLoud'),
            'project_id' => $this->get('projectId'),
            'event_type_id' => $this->get('eventTypeId'),
            'user_id' => Auth::id(),
        ];
    }
}
