<?php

namespace Artwork\Modules\ExternalAccess\Enums;

enum InviteSource: string
{
    case CRM_INDEX = 'crm_index';
    case PROJECT_TAB = 'project_tab';
    /** Einladung eines bestehenden CRM-Kontakts von dessen Detailseite aus (nur CRM-Pflege). */
    case CRM_CONTACT = 'crm_contact';
}
