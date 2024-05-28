<?php

return [
    'project' => [
        'member' => [
            'add' => 'You have been added to :project',
            'remove' => 'You have been removed from :project',
        ],
        'leader' => [
            'add' => 'You have been appointed as the project leader of :project',
            'remove' => 'You have been removed as the project leader of :project',
        ],
        'budget' => [
            'add' => 'You have received budget access in :project',
            'remove' => 'Your budget access in :project has been revoked',
            'new_verify_request' => 'New verification request',
            'delete_verify_request' => 'Verification request deleted',
            'verify_removed' => 'Verification in budget revoked',
            'fixed' => 'Budget finalized',
            'unfixed' => 'Finalization in budget revoked',
        ],
        'key_visual' => [
            'width' => 'The width of the Key Visual should be at least 1080px.',
        ],
        'file' => [
            'permission_add' => 'A document has been shared with you',
            'changed' => 'A document has been changed',
            'deleted' => 'A document has been deleted'
        ],
        'delete' => ':project has been deleted',
    ],
    'contract' => [
        'add' => 'A contract has been shared with you',
        'delete' => 'A contract has been deleted',
    ],
    'shift' => [
        'add' => 'You have been added to :shift',
        'remove' => 'You have been removed from :shift',
        'locked' => 'Shift schedule finalized',
        'short_break' => 'Shift created with insufficient break time',
        'locked_changes' => 'Shift change despite finalization',
        'new_shift_add' => 'New shift assignment :craftName :craftAbbreviation',
        'conflict' => 'Conflict with your shift',
        'conflict_text' => ':username has scheduled you on :date :from - :to contrary to your original entry.',
        'deleted_where_locked' => 'Shift deleted despite finalization :projectName :craftAbbreviation',
        'shift_staffing' => 'New shift staffing',
        'conflict_shift' => 'Shift conflict :date :from - :to',
        'your_short_break' => 'You have been scheduled with insufficient rest time',
        'worker_short_break' => 'Employee scheduled with insufficient rest time',
        'more_than_ten_days' => 'You have been scheduled for more than 10 consecutive days',
        'worker_more_than_ten_days' => 'Employee scheduled for more than 10 consecutive days',
        'shift_staffing_deleted' => 'Shift staffing deleted :projectName :craftAbbreviation',
        // 'open_demand' => 'Der Termin :event hat noch :count offene Stellen',
        'open_demand' => 'The event :event still has :count open positions',
        // open_demand_description' =>
        // 'Der Termin :event hat noch :count offene Stellen für die Gewerk :craft :shift_start - :shift_end',
        'open_demand_description' =>
            'The event :event still has :count open positions for the craft :craft :shift',
    ],
    'event' => [
        'with_adjoining_audience' => 'Event with audience in adjoining room',
        'adjoining_is_loud' => 'Loud event in adjoining room',
        'conflict' => 'Event conflict',
        'conflict_text' => 'Conflict event booked: :date_time',
        'new_room_request' => 'New room request',
        'admin_message' => 'Message from room admin',
        'room_request_with_changed_room' => 'Room request with room change confirmed',
        'new_message' => 'New message regarding room request',
        'room_request_accept' => 'Room request confirmed',
        'room_request_declined' => 'Room request declined',
        'deleted' => 'Event cancelled'
    ],
    'moneySource' => [
        'add_permission' => 'You have been granted access to :moneySource',
        'remove_permission' => 'Your access to :moneySource has been revoked',
        'deleted' => 'Funding source/group :moneySource has been deleted'
    ],
    'scheduling' => [
        'deadline_tomorrow' => 'Deadline of :checklist is reached tomorrow',
        'deadline_over' => ':checklist has exceeded its deadline',
        'new_tasks' => ':count new tasks for you',
        'changes_project' => 'There have been changes to :project',
        'changes_task' => 'Changes to :task',
        'changes_room' => 'Changes to :room',
        'changes_event' => 'Event changed',
        'public_changes_project' => 'Publicity-relevant changes to :project',
        'changes_vacation' => 'Availability changed'
    ],
    'keyWords' => [
        'concerns' => 'Concerns:',
        'concerns_shift' => 'Concerns shift:',
        'your_shift' => 'Your shift:',
        'not_available' => ':username is not available',
        'time_room' => 'Time period:',
    ],
    'department' => [
        'add' => 'Team :department has been deleted',
        'remove' => 'You have been added to team :department'
    ],
    'room' => [
        'leader' => [
            'add' => 'You have been appointed as the room admin of :room',
            'remove' => 'You have been removed as the room admin of :room'
        ],
    ]
];
