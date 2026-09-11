/**
 * Katalog der Anzeigeeinstellungen (Zahnrad-Modal) — EINE Quelle für:
 *   - welche Einstellung in welcher Ansicht erscheint (views),
 *   - Beschriftung/Beschreibung (Übersetzungs-Keys),
 *   - Sonderfälle (Standardwert, deaktiviert, Abhängigkeiten, gegenseitiger Ausschluss).
 *
 * Das Modal (CalendarSettingsModal.vue) rendert nur noch diesen Katalog; der Aktiv-Punkt am
 * Zahnrad (FunctionBarSetting.vue) liest dieselbe Liste. Eine Einstellung, die hier nicht
 * für eine Ansicht eingetragen ist, wird dort weder angezeigt noch gespeichert.
 *
 * REGEL: Nur Einstellungen eintragen, die in der jeweiligen Ansicht auch gelesen werden
 * (Entscheidung Jannik 08.09.2026 — keine wirkungslosen Häkchen).
 */

export const VIEW = Object.freeze({
    CALENDAR: 'calendar',               // Standard-Kalender (Wochen-/Monatsraster)
    CALENDAR_DAILY: 'calendar_daily',   // Kalender-Tagesansicht
    PLANNING: 'planning',               // Planungskalender
    PLANNING_DAILY: 'planning_daily',   // Planungskalender-Tagesansicht
    SHIFT_WEEK: 'shift_week',           // Dienstplan Wochenansicht
    SHIFT_DAY: 'shift_day',             // Dienstplan Tagesansicht
    PROJECT_SHIFT_TAB: 'project_shift_tab', // Projekt-Schichten-Tab (Tagesansicht im Projekt)
    SHIFT_LIST: 'shift_list',           // Dienstplan Listenansicht
});

const CALENDAR_VIEWS = [VIEW.CALENDAR, VIEW.CALENDAR_DAILY, VIEW.PLANNING, VIEW.PLANNING_DAILY];
const CALENDAR_GRID_VIEWS = [VIEW.CALENDAR, VIEW.PLANNING];          // ohne Tagesansichten
const SHIFT_PLAN_VIEWS = [VIEW.SHIFT_WEEK, VIEW.SHIFT_DAY, VIEW.PROJECT_SHIFT_TAB];
const SHIFT_DAY_VIEWS = [VIEW.SHIFT_DAY, VIEW.PROJECT_SHIFT_TAB];
const ALL_VIEWS = Object.values(VIEW);

/** Modal-Props → Ansichts-ID */
export function resolveView({ isPlanning = false, inShiftPlan = false, isDailyView = false, isListView = false, isInProjectView = false }) {
    if (isListView) return VIEW.SHIFT_LIST;
    if (inShiftPlan) {
        if (isDailyView) return isInProjectView ? VIEW.PROJECT_SHIFT_TAB : VIEW.SHIFT_DAY;
        return VIEW.SHIFT_WEEK;
    }
    if (isPlanning) return isDailyView ? VIEW.PLANNING_DAILY : VIEW.PLANNING;
    return isDailyView ? VIEW.CALENDAR_DAILY : VIEW.CALENDAR;
}

export const isShiftPlanView = (view) => SHIFT_PLAN_VIEWS.includes(view);
export const isCalendarView = (view) => CALENDAR_VIEWS.includes(view);

/**
 * Abschnitte in Anzeigereihenfolge. `columns` = Spalten des Rasters.
 * Ein Abschnitt erscheint nur, wenn mindestens ein Eintrag sichtbar ist.
 */
export const SECTIONS = [
    { id: 'navigation', title: 'Navigation', columns: 1 },
    { id: 'appearance', title: 'Appearance & Accessibility', columns: 2 },
    { id: 'visibility', title: 'Visibility & Filters', columns: 2 },
    { id: 'content', title: 'Event Content', columns: 2 },
    { id: 'list_layout', title: 'List layout', columns: 2 },
    { id: 'planning', title: 'Planning Mode', columns: 2 },
];

/**
 * Einträge. Felder:
 *   key          Spaltenname in den Settings-Tabellen (= Formularfeld)
 *   section      SECTIONS.id
 *   views        Ansichten, in denen die Einstellung erscheint (und gelesen wird)
 *   label        Übersetzungs-Key
 *   description  Übersetzungs-Key
 *   when(ctx)    optionale Zusatzbedingung (Modul aktiv, Recht vorhanden)
 *   default      Standardwert, falls die Settings-Zeile den Wert (noch) nicht hat (Wert oder fn(ctx))
 *   disabled(ctx, form)      optional: Checkbox gesperrt
 *   disabledDescription      optional: Beschreibung im gesperrten Zustand
 *   indent       optional: eingerückt (Unterpunkt einer anderen Einstellung)
 *   onChange(form, value)    optional: Folgeänderungen im Formular
 *   type         'checkbox' (Standard) | 'room_column_width' (Listbox)
 *   indicator    false = zählt nicht für den Aktiv-Punkt am Zahnrad (Einstellungen, die standardmäßig AN sind)
 */
export const SETTINGS = [
    // ---------------------------------------------------------------- Navigation
    {
        key: 'share_calendar_date',
        section: 'navigation',
        views: ALL_VIEWS,
        label: 'Share time period across views',
        description: 'Calendar, planning calendar, shift plan, list view and article planning always show the same time period. Changing the date in one view applies it everywhere.',
        // users-Spalte, ansichtsübergreifend — Startwert kommt aus auth.user statt aus den Settings
        default: (ctx) => ctx.page.auth?.user?.share_calendar_date ?? false,
    },

    // ------------------------------------------------- Darstellung & Barrierefreiheit
    {
        key: 'high_contrast',
        section: 'appearance',
        views: [...CALENDAR_VIEWS, ...SHIFT_PLAN_VIEWS],
        label: 'High contrast',
        description: 'Increases the color intensity in the calendar to make texts and elements more clearly visible, ideal for better readability and accessibility.',
    },
    {
        // Dienstplan-Tagesansicht hat keine Tageszellen → dort ohne Wirkung
        key: 'expand_days',
        section: 'appearance',
        views: [...CALENDAR_VIEWS, VIEW.SHIFT_WEEK],
        label: 'Expand days',
        description: 'Expands all days in the calendar automatically, so you can see all events at a glance without having to scroll.',
    },
    {
        key: 'show_artist_names_as_title',
        section: 'appearance',
        views: CALENDAR_GRID_VIEWS,
        label: 'Artist names instead of event title',
        description: 'In the compact view (zoom below 80%), the artist names of the project are shown instead of the event title.',
    },
    {
        // Tagesbemerkungen: nur bei aktivem Instanz-Setting & Sichtrecht; bei Pflicht gesperrt (immer an)
        key: 'show_day_remarks',
        section: 'appearance',
        views: CALENDAR_GRID_VIEWS,
        label: 'Day remarks',
        description: 'Shows the day remarks column next to the date column in the calendar.',
        when: (ctx) => ctx.dayRemarks.enabled && ctx.dayRemarks.can_view,
        default: (ctx) => ctx.dayRemarks.mandatory ? true : (ctx.settings?.show_day_remarks ?? true),
        disabled: (ctx) => ctx.dayRemarks.mandatory,
        disabledDescription: 'In your artwork the day remarks column is set as mandatory.',
        indicator: false,
    },
    {
        key: 'use_event_status_color',
        section: 'appearance',
        views: CALENDAR_VIEWS,
        label: 'Use event status colour',
        description: 'Colors calendar entries according to their status, making it easier to quickly identify scheduled, confirmed or completed events, for example.',
        when: (ctx) => !!ctx.page.event_status_module,
        // schließt die Hauptkategorie-Farbe aus
        onChange: (form, value) => { if (value) form.use_main_category_color = false; },
    },
    {
        key: 'use_main_category_color',
        section: 'appearance',
        views: CALENDAR_VIEWS,
        label: 'Event colour by main category',
        description: 'Colors calendar entries according to the main category of the project. Events without a main category are shown in anthracite, events without a project in grey.',
        onChange: (form, value) => { if (value) form.use_event_status_color = false; },
    },
    {
        // Raumspaltenbreite (Listbox) — nur Standard-/Planungskalender, Spalte existiert nur auf user_calendar_settings
        key: 'calendar_column_width',
        type: 'room_column_width',
        section: 'appearance',
        views: CALENDAR_GRID_VIEWS,
        label: 'Room column width',
        description: 'The room column width stays the same at every zoom level. The zoom only controls the row height.',
        default: 212,
    },

    // ----------------------------------------------------------- Sichtbarkeit & Filter
    {
        key: 'hide_unoccupied_rooms',
        section: 'visibility',
        views: [...CALENDAR_VIEWS, ...SHIFT_PLAN_VIEWS],
        label: 'Hide unoccupied rooms',
        description: 'Hides rooms in the calendar in which no events are entered, for a clearer display of active areas.',
    },
    {
        // Wochenansicht des Dienstplans liest den Wert nicht (bewusst: Tagesansicht-Feature)
        key: 'hide_unoccupied_days',
        section: 'visibility',
        views: SHIFT_DAY_VIEWS,
        label: 'Hide unoccupied days',
        description: 'Hides days in the calendar on which no events are scheduled, for a more focused view of active days.',
    },
    {
        key: 'show_planned_events',
        section: 'visibility',
        views: [VIEW.CALENDAR, VIEW.CALENDAR_DAILY],
        label: 'Show planned events',
        description: 'Shows provisionally entered, not yet confirmed dates, helpful for orientation in further planning.',
        when: (ctx) => ctx.can('can see planning calendar') || ctx.can('can edit planning calendar') || ctx.is('artwork admin'),
    },
    {
        key: 'show_only_not_fully_staffed_shifts',
        section: 'visibility',
        views: SHIFT_PLAN_VIEWS,
        label: 'Only show shifts that are not fully staffed',
        description: 'Only displays shifts where at least one position still has capacity for additional staff.',
    },
    {
        key: 'show_project_assignments',
        section: 'visibility',
        views: SHIFT_DAY_VIEWS,
        label: 'Show project assignments',
        description: 'Shows the assigned persons overview and the avatars in the day bars of the project shift tab.',
        default: (ctx) => ctx.settings?.show_project_assignments ?? true,
        indicator: false,
    },
    {
        key: 'show_unrelated_events',
        section: 'visibility',
        views: [VIEW.PROJECT_SHIFT_TAB],
        label: 'Show events from other projects',
        description: 'Also displays events from other projects or without a project assignment to keep the context of the whole house in view.',
    },
    {
        key: 'show_unrelated_shifts',
        section: 'visibility',
        views: [VIEW.PROJECT_SHIFT_TAB],
        label: 'Show shifts from other projects',
        description: 'Also displays shifts from other projects or without a project assignment to keep the context of the whole house in view.',
    },
    {
        key: 'repeating_events',
        section: 'visibility',
        views: CALENDAR_VIEWS,
        label: 'Repeat event',
        description: 'Indicates events that take place regularly, ideal for planning recurring meetings or rehearsals.',
    },
    {
        // Dienstplan-Tagesansicht liest den Wert nicht
        key: 'display_project_groups',
        section: 'visibility',
        views: [...CALENDAR_VIEWS, VIEW.SHIFT_WEEK],
        label: 'Show project group',
        description: 'Shows the associated project group of an event in the calendar, helpful for assignment and overview with several groups.',
    },
    {
        // Nur Kalender: im Dienstplan sind Schichten immer sichtbar
        key: 'work_shifts',
        section: 'visibility',
        views: CALENDAR_VIEWS,
        label: 'Show shifts',
        description: 'Shows standalone shifts as separate cards in the matching room-day cells of the calendar, mixed with the events and sorted by start time.',
    },
    {
        // Dienstplan-Tagesansicht: Timelines sind immer Teil der Karte
        key: 'show_timeline',
        section: 'visibility',
        views: [...CALENDAR_VIEWS, VIEW.SHIFT_WEEK],
        label: 'Timeline',
        description: 'Zeige Icon an um vorhandene Timelines von Terminen anzuzeigen und Timelines zu ergänzen',
    },
    // Listenansicht
    {
        key: 'show_fully_staffed_shifts',
        section: 'visibility',
        views: [VIEW.SHIFT_LIST],
        label: 'Show fully staffed shifts',
        description: 'Also displays shifts that are already fully staffed in the list view.',
    },
    {
        key: 'show_appointments',
        section: 'visibility',
        views: [VIEW.SHIFT_LIST],
        label: 'Show appointments',
        description: 'Shows appointments alongside shifts in a separate column.',
    },
    {
        key: 'group_by_shift_groups',
        section: 'visibility',
        views: [VIEW.SHIFT_LIST],
        label: 'Sort by shift groups',
        description: 'Groups shifts by their shift group within each room.',
    },

    // ----------------------------------------------------------------- Termininhalt
    {
        key: 'event_name',
        section: 'content',
        views: CALENDAR_VIEWS,
        label: 'Event name',
        description: 'The title or name of the calendar entry provides information about the content or purpose of the event at a glance.',
    },
    {
        key: 'description',
        section: 'content',
        views: CALENDAR_VIEWS,
        label: 'Description',
        description: 'Contains additional information or notes about the event, perfect for recording details or special features.',
    },
    {
        key: 'project_status',
        section: 'content',
        views: [...CALENDAR_VIEWS, ...SHIFT_PLAN_VIEWS],
        label: 'Project Status',
        description: 'Shows the current status of the project in the calendar, from planning to implementation, to keep an eye on progress at all times.',
    },
    {
        key: 'project_management',
        section: 'content',
        views: [...CALENDAR_VIEWS, ...SHIFT_PLAN_VIEWS],
        label: 'Project managers',
        description: 'List the responsible project managers in the calendar and ensure clarity as to who is responsible for coordination and management.',
    },
    {
        key: 'show_event_creator',
        section: 'content',
        views: [...CALENDAR_VIEWS, ...SHIFT_PLAN_VIEWS],
        label: 'Event creator',
        description: 'Shows the profile picture of the person who created the event, helpful to know who to contact with questions.',
    },
    {
        key: 'show_event_admission',
        section: 'content',
        views: [...CALENDAR_VIEWS, ...SHIFT_PLAN_VIEWS],
        label: 'Admission',
        description: 'Shows the admission time on event tiles, helpful for everyone preparing the house opening.',
        when: (ctx) => !!ctx.page.event_admission_module,
        // Default AN; fehlender Wert (Alt-Settings) zählt als aktiviert
        default: (ctx) => ctx.settings?.show_event_admission ?? true,
        indicator: false,
    },
    {
        key: 'show_event_status',
        section: 'content',
        views: CALENDAR_VIEWS,
        label: 'Event status spelled out',
        description: 'Shows the event status by name in its own row below the event name on the event tile.',
        when: (ctx) => !!ctx.page.event_status_module,
    },
    {
        key: 'project_artists',
        section: 'content',
        views: [...CALENDAR_VIEWS, ...SHIFT_PLAN_VIEWS],
        label: 'Artists',
        description: 'Shows the artists involved in the project in the calendar, helpful for an overview of who is involved and when.',
    },
    {
        // Tages- und Listenansicht zeigen Funktionen immer
        key: 'show_qualifications',
        section: 'content',
        views: [VIEW.SHIFT_WEEK],
        label: 'Show qualifications',
        description: 'Shows the required or existing qualifications of a shift in the calendar, helpful for precise shift planning.',
    },
    {
        // Beschreibung von Terminen UND Schichten (Spalte heißt historisch shift_notes)
        key: 'shift_notes',
        section: 'content',
        views: SHIFT_PLAN_VIEWS,
        label: 'Show description',
        description: 'Shows the stored description of events and shifts in the shift plan, handy for having additional information directly in view.',
    },
    {
        key: 'show_shift_group_tag',
        section: 'content',
        views: SHIFT_PLAN_VIEWS,
        label: 'Show Shift group tag',
        description: 'Displays the assigned shift group tags in the calendar, useful for quickly identifying and categorizing shifts.',
    },

    // ------------------------------------------------------------ Listendarstellung
    {
        key: 'detailed_shift_overview',
        section: 'list_layout',
        views: [VIEW.SHIFT_LIST],
        label: 'Detailed function overview per shift',
        description: 'Shows assigned persons and unoccupied slots directly under each shift in the list view.',
        // Unterpunkt „Schichtzeile ausblenden" ist nur mit Detailübersicht sinnvoll
        onChange: (form, value) => { if (!value) form.hide_shift_row = false; },
    },
    {
        key: 'hide_shift_row',
        section: 'list_layout',
        views: [VIEW.SHIFT_LIST],
        label: 'Hide shift row',
        description: 'Hides the shift header row and shows the craft abbreviation in front of each user row time.',
        indent: true,
        disabled: (ctx, form) => !form.detailed_shift_overview,
    },
    {
        key: 'shift_notes',
        section: 'list_layout',
        views: [VIEW.SHIFT_LIST],
        label: 'Show description',
        description: 'Shows the stored description of shifts and events on a separate line in the list view.',
    },
    {
        key: 'show_shift_group_tag',
        section: 'list_layout',
        views: [VIEW.SHIFT_LIST],
        label: 'Show Shift group tag',
        description: 'Displays the assigned shift group tags in the calendar, useful for quickly identifying and categorizing shifts.',
    },

    // ---------------------------------------------------------------- Planungsmodus
    {
        key: 'show_unplanned_events',
        section: 'planning',
        views: [VIEW.PLANNING, VIEW.PLANNING_DAILY],
        label: 'Show fixed events',
        description: 'Highlights firmly scheduled events in the calendar, ideal for quickly recognizing binding times.',
    },
];

/** Einträge einer Ansicht (inkl. when-Bedingung) in Katalogreihenfolge */
export function settingsForView(view, ctx) {
    return SETTINGS.filter((item) => item.views.includes(view) && (!item.when || item.when(ctx)));
}

/** Abschnitte mit ihren sichtbaren Einträgen (leere Abschnitte entfallen) */
export function sectionsForView(view, ctx) {
    const items = settingsForView(view, ctx);
    return SECTIONS
        .map((section) => ({ ...section, items: items.filter((item) => item.section === section.id) }))
        .filter((section) => section.items.length > 0);
}

/**
 * Schlüssel, die für den Aktiv-Punkt am Zahnrad zählen: alle Checkboxen der Ansicht
 * außer der ansichtsübergreifenden Zeitraum-Kopplung und Einstellungen, die standardmäßig AN sind.
 */
export function activeIndicatorKeys(view, ctx) {
    return settingsForView(view, ctx)
        .filter((item) => (item.type ?? 'checkbox') === 'checkbox' && item.indicator !== false && item.key !== 'share_calendar_date')
        .map((item) => item.key);
}

/** Startwert eines Eintrags aus den gespeicherten Settings (mit Katalog-Default) */
export function initialValue(item, ctx) {
    if (typeof item.default === 'function') return item.default(ctx);
    const stored = ctx.settings?.[item.key];
    if (stored !== undefined && stored !== null) return stored;
    return item.default ?? false;
}
