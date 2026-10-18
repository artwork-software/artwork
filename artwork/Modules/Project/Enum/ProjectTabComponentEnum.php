<?php

namespace Artwork\Modules\Project\Enum;

enum ProjectTabComponentEnum: string
{
    // custom tab component types
    case CHECKBOX = 'Checkbox';
    case TEXT_FIELD = 'TextField';
    case DROPDOWN = 'DropDown';
    case TEXT_AREA = 'TextArea';
    case TITLE = 'Title';
    case LINK = 'Link';

    case LINK_LIST = 'LinkList';


    // default tab component types
    case PROJECT_GROUP_DISPLAY = 'ProjectGroupDisplayComponent';
    case GROUP_PROJECT_DISPLAY = 'GroupProjectDisplayComponent';

    case DISCLOSURE_COMPONENT = 'DisclosureComponent';

    case PROJECT_STATUS = 'ProjectStateComponent';
    case PROJECT_GROUP = 'ProjectGroupComponent';
    case PROJECT_TEAM = 'ProjectTeamComponent';
    case PROJECT_ATTRIBUTES = 'ProjectAttributesComponent';
    case CALENDAR = 'CalendarTab';
    case CHECKLIST = 'ChecklistComponent';
    case CHECKLIST_ALL = 'ChecklistAllComponent';
    case SHIFT_TAB = 'ShiftTab';
    case RELEVANT_DATES_FOR_SHIFT_PLANNING = 'RelevantDatesForShiftPlanningComponent';
    case SHIFT_CONTACT_PERSONS = 'ShiftContactPersonsComponent';
    case GENERAL_SHIFT_INFORMATION = 'GeneralShiftInformationComponent';
    case BUDGET = 'BudgetTab';
    case PROJECT_BUDGET_DEADLINE = 'ProjectBudgetDeadlineComponent';
    case PROJECT_PERIOD = 'ProjectPeriodComponent';
    case COMMENT_TAB = 'CommentTab';
    case COMMENT_ALL_TAB = 'CommentAllTab';
    case PROJECT_DOCUMENTS = 'ProjectDocumentsComponent';
    case PROJECT_ALL_DOCUMENTS = 'ProjectAllDocumentsComponent';
    case PROJECT_TITLE = 'ProjectTitleComponent';
    case SEPARATOR = 'SeparatorComponent';

    case BUDGET_INFORMATIONS = 'BudgetInformations';

    case BULK_EDIT = 'BulkBody';

    case ARTIST_RESIDENCIES = 'ArtistResidenciesComponent';
    case ARTIST_NAME_DISPLAY = 'ArtistNameDisplayComponent';
    case PROJECT_BASIC_DATA_DISPLAY = 'ProjectBasicDataDisplayComponent';
    case PROJECT_COST_CENTER_DISPLAY = 'ProjectCostCenterDisplayComponent';
    case PROJECT_MATERIAL_ISSUE_COMPONENT = 'ProjectMaterialIssueComponent';
    case PROJECT_CONTRACTS_DOCUMENTS = 'ProjectContractsDocumentsComponent';
    case BUSINESS_INTELLIGENCE = 'BusinessIntelligenceComponent';
    case BI_KEY_FIGURES = 'BiKeyFiguresDisplay';
    case SAGE_INVOICE_OVERVIEW = 'SageInvoiceOverviewComponent';
    // CRM-Kontakte des Projekts je Komponente (anlegen/verknüpfen, auch durch Externe)
    case CRM_CONTACT_LIST = 'CrmContactListComponent';
    case TICKETING = 'TicketingTab';

    /**
     * Component types that may be rendered in the external tab view.
     * Internal visibility settings (ComponentUser/ComponentDepartment) are
     * deliberately ignored for external users — this is the only filter that
     * decides whether the external renderer can show a component at all.
     */
    private const EXTERNALLY_READABLE = [
        // Custom components (user-configurable) — all readable
        self::CHECKBOX,
        self::TEXT_FIELD,
        self::DROPDOWN,
        self::TEXT_AREA,
        self::TITLE,
        self::LINK,
        self::LINK_LIST,
        self::SEPARATOR,
        self::DISCLOSURE_COMPONENT,
        // Default components that make sense to expose read-only to externals
        self::PROJECT_TITLE,
        self::PROJECT_BASIC_DATA_DISPLAY,
        self::ARTIST_NAME_DISPLAY,
        // Dokumente des freigegebenen Tabs (Upload/Download über eigene externe Endpunkte)
        self::PROJECT_DOCUMENTS,
        self::CRM_CONTACT_LIST,
    ];

    /**
     * Component types an external user with a write scope may edit. Only custom
     * components — no default/system components (layout-only or side-effect heavy).
     */
    private const EXTERNALLY_WRITABLE = [
        self::CHECKBOX,
        self::TEXT_FIELD,
        self::DROPDOWN,
        self::TEXT_AREA,
        self::LINK,
        self::LINK_LIST,
        self::DISCLOSURE_COMPONENT,
        // Schreibend = Dateien hochladen/eigene Uploads löschen (ExternalProjectFileService)
        self::PROJECT_DOCUMENTS,
        // Schreibend = eigene Kontakte anlegen/bearbeiten/entfernen (ProjectComponentCrmContactService)
        self::CRM_CONTACT_LIST,
    ];

    /**
     * Component types that can be placed into a project print layout. This is the
     * single source of truth for the selectable palette: a type is offered here
     * ONLY if it has both (a) a dedicated print renderer (PrintLayoutBuilder* Vue
     * component registered in ProjectPrintLayoutWindow.vue's componentMapping) and
     * (b) data preparation in ProjectPrintLayoutController::show(). This guarantees
     * "every selectable component is actually visible on the generated PDF".
     *
     * Keep in sync with `componentMapping` in ProjectPrintLayoutWindow.vue.
     */
    private const PRINTABLE = [
        // Custom (user-configurable) components — rendered from stored values
        self::CHECKBOX,
        self::TEXT_FIELD,
        self::DROPDOWN,
        self::TEXT_AREA,
        self::TITLE,
        self::LINK,
        self::LINK_LIST,
        self::SEPARATOR,
        // Special / system components with a dedicated print renderer
        self::PROJECT_TITLE,
        self::PROJECT_STATUS,
        self::PROJECT_GROUP,
        self::PROJECT_TEAM,
        self::PROJECT_ATTRIBUTES,
        self::PROJECT_PERIOD,
        self::RELEVANT_DATES_FOR_SHIFT_PLANNING,
        self::SHIFT_CONTACT_PERSONS,
        self::GENERAL_SHIFT_INFORMATION,
        self::SHIFT_TAB,
        self::PROJECT_BUDGET_DEADLINE,
        self::BUDGET_INFORMATIONS,
        self::BULK_EDIT,
        self::ARTIST_RESIDENCIES,
        self::BUSINESS_INTELLIGENCE,
        self::BI_KEY_FIGURES,
        self::PROJECT_ALL_DOCUMENTS,
        self::COMMENT_ALL_TAB,
        self::CHECKLIST_ALL,
        self::ARTIST_NAME_DISPLAY,
        self::PROJECT_BASIC_DATA_DISPLAY,
        self::PROJECT_COST_CENTER_DISPLAY,
        self::PROJECT_MATERIAL_ISSUE_COMPONENT,
        self::PROJECT_CONTRACTS_DOCUMENTS,
        self::CRM_CONTACT_LIST,
    ];

    /**
     * Große Layout-Komponenten, die in Ordnern (DisclosureComponent) nicht funktionieren.
     * Frontend-Gegenstück: resources/js/Pages/Projects/Tab/projectTabComponentRules.js
     */
    private const FOLDER_BLOCKED = [
        self::CALENDAR,
        self::SHIFT_TAB,
        self::BUDGET,
        self::TICKETING,
        self::BULK_EDIT,
        self::CHECKLIST_ALL,
        self::COMMENT_ALL_TAB,
        self::PROJECT_ALL_DOCUMENTS,
    ];

    /**
     * Komponenten, die beim Hinzufügen eine Tab-Auswahl (Scope) brauchen.
     */
    private const SCOPED = [
        self::PROJECT_DOCUMENTS,
        self::COMMENT_TAB,
        self::CHECKLIST,
    ];

    public function canBePlacedInFolder(): bool
    {
        return $this !== self::DISCLOSURE_COMPONENT && !in_array($this, self::FOLDER_BLOCKED, true);
    }

    public function requiresScope(): bool
    {
        return in_array($this, self::SCOPED, true);
    }

    /**
     * @return array<int, string>
     */
    public static function folderBlockedValues(): array
    {
        return array_map(static fn (self $case): string => $case->value, self::FOLDER_BLOCKED);
    }

    /**
     * @return array<int, string>
     */
    public static function scopedValues(): array
    {
        return array_map(static fn (self $case): string => $case->value, self::SCOPED);
    }

    public function isPrintable(): bool
    {
        return in_array($this, self::PRINTABLE, true);
    }

    /**
     * Enum string values of all print-layout-capable component types.
     * @return array<int, string>
     */
    public static function printableValues(): array
    {
        return array_map(static fn (self $case): string => $case->value, self::PRINTABLE);
    }

    /**
     * Custom (user-configurable) value components the app renders from
     * the stored project value.
     */
    private const APP_CUSTOM = [
        self::CHECKBOX,
        self::TEXT_FIELD,
        self::DROPDOWN,
        self::TEXT_AREA,
        self::TITLE,
        self::LINK,
        self::LINK_LIST,
        self::SEPARATOR,
        self::DISCLOSURE_COMPONENT,
    ];

    /**
     * System components with a read-only app rendering; their `value`
     * payload is built server-side by AppSystemComponentService. The
     * remaining system types (budget table, documents, BI, …) stay web-only —
     * they need dedicated designs to be usable on a phone.
     */
    private const APP_SYSTEM = [
        self::PROJECT_TITLE,
        self::PROJECT_STATUS,
        self::PROJECT_TEAM,
        self::PROJECT_ATTRIBUTES,
        self::PROJECT_PERIOD,
        self::GENERAL_SHIFT_INFORMATION,
        self::SHIFT_CONTACT_PERSONS,
        self::PROJECT_BUDGET_DEADLINE,
        self::BUDGET_INFORMATIONS,
        self::CALENDAR,
        self::BULK_EDIT,
        self::SHIFT_TAB,
        self::CHECKLIST,
        self::CHECKLIST_ALL,
        self::COMMENT_TAB,
        self::COMMENT_ALL_TAB,
        self::PROJECT_DOCUMENTS,
        self::PROJECT_ALL_DOCUMENTS,
    ];

    /**
     * Component types the app may write values for. Layout-only types
     * (Title, Separator, Disclosure) carry no project value.
     */
    private const APP_WRITABLE = [
        self::CHECKBOX,
        self::TEXT_FIELD,
        self::DROPDOWN,
        self::TEXT_AREA,
        self::LINK,
        self::LINK_LIST,
    ];

    /**
     * Stable domain names of the component types on the app wire. The enum
     * values are web-internal class names (BulkBody, BudgetInformations, …) —
     * the app contract speaks kebab-case domain vocabulary instead.
     * CALENDAR and BULK_EDIT render identically in the app and share one name.
     */
    private const APP_WIRE_TYPES = [
        self::TITLE->value => 'title',
        self::TEXT_FIELD->value => 'text-field',
        self::TEXT_AREA->value => 'text-area',
        self::LINK->value => 'link',
        self::CHECKBOX->value => 'checkbox',
        self::DROPDOWN->value => 'dropdown',
        self::LINK_LIST->value => 'link-list',
        self::SEPARATOR->value => 'separator',
        self::DISCLOSURE_COMPONENT->value => 'disclosure',
        self::PROJECT_TITLE->value => 'project-title',
        self::PROJECT_STATUS->value => 'project-state',
        self::PROJECT_TEAM->value => 'project-team',
        self::PROJECT_ATTRIBUTES->value => 'project-attributes',
        self::PROJECT_PERIOD->value => 'project-period',
        self::GENERAL_SHIFT_INFORMATION->value => 'shift-information',
        self::SHIFT_CONTACT_PERSONS->value => 'shift-contacts',
        self::PROJECT_BUDGET_DEADLINE->value => 'budget-deadline',
        self::BUDGET_INFORMATIONS->value => 'budget-information',
        self::CALENDAR->value => 'calendar',
        self::BULK_EDIT->value => 'calendar',
        self::SHIFT_TAB->value => 'shifts',
        self::CHECKLIST->value => 'checklist',
        self::CHECKLIST_ALL->value => 'checklist-all',
        self::COMMENT_TAB->value => 'comments',
        self::COMMENT_ALL_TAB->value => 'comments-all',
        self::PROJECT_DOCUMENTS->value => 'documents',
        self::PROJECT_ALL_DOCUMENTS->value => 'documents-all',
    ];

    public function appWireType(): string
    {
        return self::APP_WIRE_TYPES[$this->value] ?? $this->value;
    }

    public function isExternallyReadable(): bool
    {
        return in_array($this, self::EXTERNALLY_READABLE, true);
    }

    public function isExternallyWritable(): bool
    {
        return in_array($this, self::EXTERNALLY_WRITABLE, true);
    }

    public function isAppReadable(): bool
    {
        return in_array($this, self::APP_CUSTOM, true) || in_array($this, self::APP_SYSTEM, true);
    }

    public function isAppSystem(): bool
    {
        return in_array($this, self::APP_SYSTEM, true);
    }

    public function isAppWritable(): bool
    {
        return in_array($this, self::APP_WRITABLE, true);
    }

    /**
     * Enum string values of all app-readable component types.
     * @return array<int, string>
     */
    public static function appReadableValues(): array
    {
        return array_map(
            static fn (self $case): string => $case->value,
            [...self::APP_CUSTOM, ...self::APP_SYSTEM],
        );
    }

    /**
     * Get all available values
     * @return array<string, mixed>
     */
    public static function getValues(): array
    {
        return [
            self::CHECKBOX->value => [
                'name' => self::CHECKBOX->value,
                'availableFields' => [
                    'label' => '',
                    'checked' => '',
                ]
            ],
            self::TEXT_FIELD->value => [
                'name' => self::TEXT_FIELD->value,
                'availableFields' => [
                    'label' => '',
                    'text' => '',
                    'placeholder' => '',
                ]
            ],
            self::LINK->value => [
                'name' => self::LINK->value,
                'availableFields' => [
                    'label' => '',
                    'text' => '',
                    'placeholder' => '',
                ]
            ],
            self::DROPDOWN->value => [
                'name' => self::DROPDOWN->value,
                'availableFields' => [
                    'label' => '',
                    'options' => [
                        [
                            'value' => '',
                        ]
                    ],
                    'selected' => '',
                ]
            ],
            self::TEXT_AREA->value => [
                'name' => self::TEXT_AREA->value,
                'availableFields' => [
                    'label' => '',
                    'text' => '',
                    'placeholder' => '',
                ]
            ],
            self::TITLE->value => [
                'name' => self::TITLE->value,
                'availableFields' => [
                    'title' => '',
                    'title_size' => 12,
                    // optionaler Text unter der Überschrift (z. B. Erklärung zum Abschnitt)
                    'subtitle' => '',
                    // Hex-Farbe: Überschrift als farbiger Abschnittsbalken; leer = schlichte Überschrift
                    'bar_color' => '',
                ]
            ],
            self::SEPARATOR->value => [
                'name' => self::SEPARATOR->value,
                'availableFields' => [
                    'height' => 0,
                    'showLine' => false,
                ]
            ],
            self::DISCLOSURE_COMPONENT->value => [
                'name' => self::DISCLOSURE_COMPONENT->value,
                'availableFields' => [
                    'label' => '',
                ]
            ],
            self::LINK_LIST->value => [
                'name' => 'LinkList',
                'availableFields' => [
                    'title' => '',
                    'label' => 'Linkliste',
                    'placeholder_label' => 'Anzeige',
                    'placeholder_url' => 'https://…',
                    'max_items' => 20,
                ],
            ],
            self::CRM_CONTACT_LIST->value => [
                'name' => self::CRM_CONTACT_LIST->value,
                'availableFields' => [
                    'title' => '',
                    'description' => '',
                    // Kontakttypen, die in dieser Liste angelegt/verknüpft werden dürfen
                    'contact_type_ids' => [],
                    // leer = unbegrenzt
                    'max_contacts' => null,
                ],
            ],
        ];
    }
}
