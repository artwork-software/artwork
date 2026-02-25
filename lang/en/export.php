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
    'total_daily_allowance' => 'Total Daily Allowance',
    'breakfast_count' => 'Number of Breakfasts',
    'breakfast_deduction_total' => 'Less Breakfast at Hotel',
    'payout_per_diem' => 'Payout Per Diem',
    'per_diem_number' => 'Per Diem Number',
    'production' => 'Production',
    'cost_bearer' => 'Cost Bearer',
    'details' => 'Details',
    'confirmation_title' => 'Confirmation of factual accuracy',
    'confirmation_text' => 'It is hereby confirmed that the above-mentioned persons were active in the context of the production and that the meal allowances comply with the applicable guidelines.',
    'name_artist' => 'Name Artist',
    'civil_name' => 'Civil Name',
    'phone_number' => 'Phone Number',
    'position' => 'Position',

    // Contract export translations
    'contract_name' => 'Contract Name',
    'contract_partner' => 'Contract Partner',
    'project' => 'Project',
    'contract_type' => 'Contract Type',
    'company_type' => 'Legal Form',
    'amount' => 'Amount',
    'currency' => 'Currency',
    'description' => 'Description',
    'ksk_liable' => 'KSK-liable',
    'ksk_amount' => 'KSK Amount',
    'ksk_reason' => 'KSK Reason',
    'resident_abroad' => 'Resident Abroad',
    'foreign_tax' => 'Foreign Tax',
    'foreign_tax_amount' => 'Foreign Tax Amount',
    'foreign_tax_reason' => 'Foreign Tax Reason',
    'foreign_tax_city' => 'Foreign Tax City',
    'foreign_tax_country' => 'Foreign Tax Country',
    'reverse_charge_amount' => 'Reverse Charge Amount',
    'deadline_date' => 'Deadline',
    'is_freed' => 'Released',
    'has_power_of_attorney' => 'Has Power of Attorney',
    'creator' => 'Creator',
    'created_at' => 'Created At',
    'yes' => 'Yes',
    'no' => 'No',

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
