/**
 * Links from artwork into the user manual (wiki.artwork.software).
 *
 * The manual is public and the same for every installation, so the base URL is
 * fixed. Paths are the page slugs of the manual below /<lang>/docs; the manual
 * serves German under /de and English under /en.
 */
export const MANUAL_BASE_URL = 'https://wiki.artwork.software';

/**
 * Manual language for an app locale: English for "en*", German otherwise.
 *
 * @param {string|null|undefined} locale
 * @returns {'de'|'en'}
 */
export function manualLanguage(locale) {
    return String(locale ?? '').toLowerCase().startsWith('en') ? 'en' : 'de';
}

/**
 * Absolute manual URL for a page path such as "calendar/views-and-display-settings".
 *
 * @param {string} [pagePath] page slug, optionally with "#anchor"
 * @param {string|null} [locale] app locale
 * @returns {string}
 */
export function manualUrl(pagePath = '', locale = null) {
    const cleaned = String(pagePath ?? '').replace(/^\/+/, '');
    const suffix = cleaned ? `/${cleaned}` : '';
    return `${MANUAL_BASE_URL}/${manualLanguage(locale)}/docs${suffix}`;
}

/**
 * Manual page for a view of the display settings modal
 * (view ids from CalendarSettingsCatalog.js).
 *
 * @param {string} view
 * @returns {string}
 */
export function displaySettingsManualPath(view) {
    const shiftViews = ['shift_week', 'shift_day', 'project_shift_tab', 'shift_list'];
    return shiftViews.includes(view)
        ? 'shift-plan/views-and-display-settings'
        : 'calendar/views-and-display-settings';
}

/**
 * Manual page per settings area. The keys are the <area> part of the settings
 * guide banners' storage keys ('settings-guide.<area>.<tab>'); tabs that have
 * their own manual section are listed as '<area>.<tab>' and win over the area.
 */
const SETTINGS_GUIDE_PAGES = {
    tool: 'system/tool-settings',
    'tool.branding': 'system/tool-settings#branding',
    'tool.communication-and-legal': 'system/tool-settings#kommunikation--rechtliches',
    'tool.interfaces': 'system/tool-settings#schnittstellen',
    'tool.module-settings': 'system/tool-settings#modulsichtbarkeit',
    'tool.file-settings': 'system/tool-settings#dateieinstellungen',
    'tool.mail': 'system/tool-settings#mail',
    shift: 'shift-plan/settings',
    'shift.rules': 'shift-plan/rules-and-compensation',
    'shift.violations': 'shift-plan/rules-and-compensation#offene-verstöße',
    'shift.day-services': 'shift-plan/settings#tagesdienste',
    'shift.shift-groups': 'shift-plan/settings#schichtgruppen',
    'shift.shift-templates': 'shift-plan/settings#vorlagen',
    'shift.preset-groups': 'shift-plan/settings#vorlagen',
    'shift.user-contracts': 'shift-plan/settings#arbeitszeitmuster--verträge',
    'shift.work-time-patterns': 'shift-plan/settings#arbeitszeitmuster--verträge',
    project: 'projects/settings',
    'project.tabs': 'projects/tab-configuration',
    'project.components': 'projects/tab-configuration#die-bausteine',
    'project.print-layout': 'projects/settings#drucklayouts',
    'project.overview-builder': 'projects/settings#projektübersicht-builder',
    'project.roles': 'projects/settings#projektrollen',
    'project.artist-residencies': 'projects/settings#künstlerinnenaufenthalte',
    'project.bi-fields': 'projects/settings#bi-einstellungen',
    calendar: 'calendar/settings',
    event: 'calendar/event-settings',
    'event.types': 'calendar/event-settings#termintypen',
    'event.status': 'calendar/event-settings#terminstatus',
    'event.properties': 'calendar/event-settings#event-eigenschaften',
    'event.holidays': 'calendar/event-settings#feiertage--schulferien',
    'event.timeline-presets': 'calendar/event-settings#timeline-presets',
    'event.standard-values': 'calendar/event-settings#standardwerte',
    rooms: 'calendar/rooms-and-access',
    'rooms.sort': 'calendar/rooms-and-access#räume-sortieren',
    inventory: 'inventory/settings',
    'material-sets': 'inventory/material-sets',
    budget: 'projects/budget-settings',
    checklists: 'todos/templates',
    permissions: 'users/roles-and-permissions',
};

/**
 * Manual page for a settings guide banner, derived from its storage key.
 *
 * @param {string|null} storageKey e.g. 'settings-guide.shift.rules'
 * @returns {string|null} page path, or null if the banner has no manual page
 */
export function settingsGuideManualPath(storageKey) {
    const match = /^settings-guide\.([^.]+)(?:\.([^.]+))?/.exec(String(storageKey ?? ''));
    if (!match) {
        return null;
    }
    const [, area, tab] = match;
    return SETTINGS_GUIDE_PAGES[`${area}.${tab}`] ?? SETTINGS_GUIDE_PAGES[area] ?? null;
}
