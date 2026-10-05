/**
 * Global switch "Work time accounting" from the shift settings
 * (shift-settings.work_time_accounting_enabled, shared by HandleInertiaRequests).
 *
 * Off means: the duty roster is only used to create and staff shifts. Target hours,
 * work time balance, overtime and the work time pattern pages are hidden; planned
 * hours from shifts stay visible. A missing prop counts as "on", so pages rendered
 * without the shared props keep today's behaviour.
 *
 * @param {Record<string, unknown>|null|undefined} pageProps
 * @returns {boolean}
 */
export function isWorkTimeAccountingEnabled(pageProps) {
    return pageProps?.work_time_accounting_enabled !== false;
}
