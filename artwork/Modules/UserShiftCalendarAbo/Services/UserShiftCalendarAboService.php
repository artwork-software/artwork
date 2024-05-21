<?php

namespace Artwork\Modules\UserShiftCalendarAbo\Services;

use Artwork\Modules\UserShiftCalendarAbo\Models\UserShiftCalendarAbo;
use Artwork\Modules\UserShiftCalendarAbo\Repositories\UserShiftCalendarAboRepository;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class UserShiftCalendarAboService
{
    public function __construct(
        private readonly UserShiftCalendarAboRepository $userShiftCalendarAboRepository
    ) {
        //
    }

    public function create(array $data): void
    {
        $calendarAbo = new UserShiftCalendarAbo();
        $calendarAbo->user_id = Auth::id();
        // $calendarAbo->calendar_abo_id random string
        $calendarAbo->calendar_abo_id = Str::random(32);
        $calendarAbo->date_range = $data['date_range'];
        $calendarAbo->start_date = $data['start_date'];
        $calendarAbo->end_date = $data['end_date'];
        $calendarAbo->specific_event_types = $data['specific_event_types'];
        $calendarAbo->event_types = $data['event_types'];
        $calendarAbo->enable_notification = $data['enable_notification'];
        $calendarAbo->notification_time = $data['notification_time'];
        $calendarAbo->notification_time_unit = $data['notification_time_unit'];

        $this->userShiftCalendarAboRepository->save($calendarAbo);
    }
}
