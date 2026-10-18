/**
 * Regeln je Projekt-Tab-Komponententyp – eine Stelle für Einstellungen (Drag & Drop, Modal),
 * Tab-Ansicht und Icons. Spiegelt ProjectTabComponentEnum (requiresScope/canBePlacedInFolder);
 * ProjectTabComponentRegistryParityTest hält beide Seiten synchron.
 *
 * Neuer Komponententyp: Enum-Case anlegen, hier Icon (und ggf. Regeln) eintragen, Vue-Komponente
 * in projectTabComponents.js registrieren.
 */

/** Typen, die beim Hinzufügen eine Tab-Auswahl (Scope) brauchen */
export const SCOPE_COMPONENT_TYPES = ['ProjectDocumentsComponent', 'CommentTab', 'ChecklistComponent']

/** Große Layout-Komponenten, die in Ordnern nicht funktionieren */
export const FOLDER_BLOCKED_COMPONENT_TYPES = [
    'CalendarTab',
    'ShiftTab',
    'BudgetTab',
    'TicketingTab',
    'BulkBody',
    'ChecklistAllComponent',
    'CommentAllTab',
    'ProjectAllDocumentsComponent',
]

export const FOLDER_COMPONENT_TYPE = 'DisclosureComponent'

/** Tabler-Icon je Typ (PropertyIcon-Name) */
export const COMPONENT_ICONS = {
    TextField: 'IconTxt',
    Checkbox: 'IconCheckbox',
    TextArea: 'IconArticle',
    Title: 'IconHeading',
    DropDown: 'IconSelect',
    Link: 'IconLink',
    LinkList: 'IconLink',
    ProjectStateComponent: 'IconPlaystationCircle',
    ProjectGroupComponent: 'IconUsersGroup',
    ProjectTeamComponent: 'IconUserShield',
    ProjectAttributesComponent: 'IconFolderPlus',
    CalendarTab: 'IconCalendar',
    ShiftTab: 'IconCalendarUser',
    ChecklistComponent: 'IconListCheck',
    ChecklistAllComponent: 'IconListCheck',
    RelevantDatesForShiftPlanningComponent: 'IconCalendarStats',
    ProjectTitleComponent: 'IconHeading',
    ProjectDocumentsComponent: 'IconFiles',
    ProjectAllDocumentsComponent: 'IconFiles',
    CommentTab: 'IconMessageDots',
    CommentAllTab: 'IconMessageDots',
    ProjectBudgetDeadlineComponent: 'IconCalendarDue',
    BudgetTab: 'IconMoneybag',
    TicketingTab: 'IconBuildingStore',
    ShiftContactPersonsComponent: 'IconAddressBook',
    GeneralShiftInformationComponent: 'IconInfoSquare',
    SeparatorComponent: 'IconSeparator',
    BudgetInformations: 'IconCurrencyEuro',
    BulkBody: 'IconApps',
    ArtistResidenciesComponent: 'IconPalette',
    GroupProjectDisplayComponent: 'IconCornerDownRightDouble',
    ProjectGroupDisplayComponent: 'IconDeviceProjector',
    DisclosureComponent: 'IconLayoutNavbarCollapse',
    ProjectBasicDataDisplayComponent: 'IconInfoSquare',
    ProjectMaterialIssueComponent: 'IconPackage',
    ProjectCostCenterDisplayComponent: 'IconBuildingBank',
    ArtistNameDisplayComponent: 'IconPalette',
    ProjectContractsDocumentsComponent: 'IconFileText',
    BusinessIntelligenceComponent: 'IconChartHistogram',
    BiKeyFiguresDisplay: 'IconChartBar',
    ProjectPeriodComponent: 'IconCalendarEvent',
    SageInvoiceOverviewComponent: 'IconFileInvoice',
    CrmContactListComponent: 'IconAddressBook',
}

export const requiresScope = (type) => SCOPE_COMPONENT_TYPES.includes(type)

export const canBePlacedInFolder = (type) =>
    type !== FOLDER_COMPONENT_TYPE && !FOLDER_BLOCKED_COMPONENT_TYPES.includes(type)

/** Übersetzungskey, warum der Typ nicht in einen Ordner darf (null = erlaubt) */
export const folderBlockReason = (type) => {
    if (type === FOLDER_COMPONENT_TYPE) {
        return 'Folders cannot be nested inside folders'
    }
    if (FOLDER_BLOCKED_COMPONENT_TYPES.includes(type)) {
        return 'This component cannot be placed inside a folder'
    }
    return null
}

export const componentIcon = (type) => COMPONENT_ICONS[type] ?? null
