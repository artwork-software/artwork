/**
 * Buttons, bei denen sich eine Benachrichtigung ohne weitere Aktion archivieren lässt.
 * Spiegel von DatabaseNotificationService::ARCHIVABLE_BUTTONS (Parität per Test geprüft).
 */
export const ARCHIVABLE_BUTTONS = [
    'showInTasks',
    'show_project',
    'delete_shift_notification',
    'see_shift',
    'change_shift',
    'accept',
    'decline',
    'answerDialog',
    'answer',
    'change_request',
    'event_delete',
    'show_in_calendar',
    'calculation_check',
    'delete_request',
    'material_issue_return_confirm',
    'material_issue_return_decline',
]

export const isArchivable = (buttons = []) => buttons.every((button) => ARCHIVABLE_BUTTONS.includes(button))
