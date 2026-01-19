<?php

//phpcs:disable
return [
    'date-heading' => 'Date',
    'months' => [
        'january' => 'January',
        'february' => 'February',
        'march' => 'March',
        'april' => 'April',
        'may' => 'May',
        'june' => 'June',
        'july' => 'July',
        'august' => 'August',
        'september' => 'September',
        'october' => 'October',
        'november' => 'November',
        'december' => 'December',
    ],
    'shortMonths' => [
        'jan' => 'Jan',
        'feb' => 'Feb',
        'mar' => 'Mar',
        'apr' => 'Apr',
        'may' => 'May',
        'jun' => 'Jun',
        'jul' => 'Jul',
        'aug' => 'Aug',
        'sep' => 'Sep',
        'oct' => 'Oct',
        'nov' => 'Nov',
        'dec' => 'Dec',
    ],
    'names' => [
        'event-list-export-by-period' => 'Event list by period - :0 until :1 - created :2',
        'event-list-export-by-projects' => 'Event list by projects - Created :0 - :1',
        'event-calendar-export-by-period' => 'Calendar events by period - :0 until :1 - created :2',
        'event-calendar-export-by-projects' => 'Calendar events by projects - Created :0 - :1'
    ],
    'excel-event-list-export' => [
        'columns' => [
            'event_id' => 'Event ID',
            'project_name' => 'Project name',
            'artists' => 'Artists',
            'start_date' => 'Date from',
            'end_date' => 'Date until',
            'start_time' => 'Time from',
            'end_time' => 'Time to',
            'event_type' => 'Event type',
            'event_name' => 'Event name',
            'event_description' => 'Event description',
            'event_status' => 'Event status',
            'room' => 'Room',
            'project_team' => 'Project team',
            'project_properties' => 'Project properties'
        ]
    ],
    'excel-event-calendar-export' => [
        'created-by' => 'Created by :0 at :1'
    ],
    'by' => 'Export created by',
    'date' => 'Export date',
    'artist_name' => 'Artist Name',
    'arrival_time' => 'Arrival Time',
    'departure_time' => 'Departure Time',
    'nights_count' => 'Number of Nights',
    'daily_allowance' => 'Daily Allowance',
    'additional_allowance' => 'Additional Allowance',
    'cost_per_night' => 'Cost per Night',
    'total' => 'Total',
    'get_sum' => 'Amount received',
    'total_sum' => 'Total Amount',
    'total_days' => 'Total Days',
    'name_artist' => 'Name Artist',
    'civil_name' => 'Civil Name',
    'phone_number' => 'Phone Number',
    'position' => 'Position',
    'shift_plan' => [
        'title'  => ':project staffing plan',
        'period' => 'Period',

        'sections' => [
            'shifts'     => 'Shifts',
            'work_hours' => 'Work hours',
        ],

        'subsections' => [
            'internal' => 'internal',
            'external' => 'external',
            'total'    => 'Total',
        ],

        'columns' => [
            'craft'      => 'Shift',
            'date'       => 'Date',
            'room'       => 'Room',
            'start'      => 'Start',
            'end'        => 'End time',
            'duration'   => 'Total hours',
            'break_time' => 'Break time',
        ],

        'symbols' => [
            'sum' => '∑',
        ],

        'defaults' => [
            'zero_duration' => '0 h 00 min',
        ],
    ],
];
