/**
 * Zentrale Konfiguration des DateRangeControl: Ein Eintrag pro Seite, statt
 * Boolean-Prop-Kombinatorik. Neue Seite = neue Zeile.
 *
 * reload: true       → router.reload({data: {start, end}}) statt Route-Patch
 * routeName          → Ziggy-Routenname, erhält die User-Id als Parameter
 * preserveState/-Scroll → an router.patch durchgereicht
 */
export const DATE_RANGE_MODES = {
    'calendar': {
        routeName: 'update.user.calendar.filter.dates',
        preserveState: false,
        preserveScroll: true,
    },
    'shift-plan': {
        routeName: 'update.user.shift.calendar.filter.dates',
        preserveState: false,
        preserveScroll: true,
    },
    'shift-list': {
        routeName: 'update.user.shift-list-view.filter.dates',
        preserveState: false,
        preserveScroll: true,
    },
    'user-shift-plan': {
        routeName: 'update.user.worker.shift-plan.filters.update',
        preserveState: true,
        preserveScroll: true,
    },
    'article-planning': {
        routeName: 'update.user.inventory.article-plan.filters.update',
        preserveState: true,
        preserveScroll: true,
    },
    'work-times': {
        reload: true,
    },
};
