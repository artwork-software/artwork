<?php

namespace Artwork\Modules\Event\Http\Requests;

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
            'eventName' => $this->get('eventName'),
            'description' => $this->get('description'),
            'audience' => $this->get('audience'),
            'is_loud' => $this->get('isLoud'),
            'project_id' => $this->get('projectId'),
            'event_type_id' => $this->get('eventTypeId'),
            'project_id_mandatory' => $this->get('projectIdMandatory'),
            'event_name_mandatory' => $this->get('eventNameMandatory'),
            'creating_project' => $this->get('creatingProject'),
            'user_id' => Auth::id(),
            'occupancy_option' => $this->get('isOption'),
            'is_series' => $this->get('is_series'),
            'frequency' => $this->get('seriesFrequency'),
            'seriesEnd' => $this->get('seriesEndDate'),
            'allDay' => $this->get('allDay'),
        ];
    }
}
