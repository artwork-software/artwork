<?php

namespace App\Enums;

class PermissionNameEnum
{
    public const PROJECT_VIEW = 'view projects';
    public const PROJECT_UPDATE = 'create and edit projects';
    public const PROJECT_ADMIN = 'admin projects';
    public const PROJECT_DELETE = 'delete projects';
    public const PROJECT_SETTINGS_ADMIN = 'admin projectSettings';
    public const GLOBAL_NOTIFICATION_ADMIN = 'admin globalNotification';

    public const SETTINGS_UPDATE = 'change tool settings';

    public const DEPARTMENT_UPDATE = 'update departments';

    public const USER_UPDATE = 'usermanagement';

    public const TEAM_UPDATE = 'teammanagement';

    public const EVENT_TYPE_SETTINGS_ADMIN = 'admin eventTypeSettings';

    public const CHECKLIST_SETTINGS_ADMIN = 'admin checklistTemplates';
    public const CHECKLIST_UPDATE = 'update checklists';
    public const CHECKLIST_VIEW = 'view checklists';
    public const CHECKLIST_DELETE = 'delete checklists';

    public const ROOM_ADMIN = 'admin rooms';

    public const EVENT_REQUEST = 'request room occupancy';
    public const EVENT_REQUEST_CONFIRM = 'view occupancy requests';
}
