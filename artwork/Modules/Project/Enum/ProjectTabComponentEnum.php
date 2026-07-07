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
    ];

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

    public function isExternallyReadable(): bool
    {
        return in_array($this, self::EXTERNALLY_READABLE, true);
    }

    public function isExternallyWritable(): bool
    {
        return in_array($this, self::EXTERNALLY_WRITABLE, true);
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
        ];
    }
}
