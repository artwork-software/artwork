/**
 * Wer geplante Termine sehen darf – Spiegel von EventPolicy::viewPlanning: Admin (Gate::before),
 * "Planungskalender einsehen" oder "Geplante Termine bearbeiten". Steuert Planungskanäle
 * (event.planning.room.*, bulk.events.planning), "Geplante Termine anzeigen" und den Planungskalender-Link.
 *
 * Reines JS-Modul ohne Vue-Abhängigkeit (tests/Frontend/planningRights.test.js); `can` wird übergeben,
 * damit Komponenten das laravel-permission-to-vuejs-`can` bzw. ein Kontext-`can` hineinreichen.
 */

/**
 * Der Modul-Schalter `module_settings.planning_calendar` gehört bewusst NICHT dazu: Er sperrt serverseitig nur
 * die Seite /planning-event-calendar (ModuleSettingsMiddleware), nicht die Policy – geplante Termine kommen
 * auch bei abgeschaltetem Modul über Kalender, Dienstplan und Broadcasts. Für den Link zur Seite siehe
 * canOpenPlanningCalendar().
 *
 * @param {{isAdmin: boolean, can: (permission: string) => boolean}} options
 * @returns {boolean}
 */
export function canViewPlanning({ isAdmin, can }) {
    return !!isAdmin || !!can('can see planning calendar') || !!can('can edit planning calendar');
}

/**
 * Menüpunkt bzw. Sprung in den Planungskalender: viewPlanning plus eingeschaltetes Modul, sonst antwortet die
 * Seite mit der Modul-Sperre. Fehlt der Schalter in den geteilten Props, gilt das Modul als aktiv.
 *
 * @param {{isAdmin: boolean, can: (permission: string) => boolean, moduleSettings?: Record<string, boolean>|null}} options
 * @returns {boolean}
 */
export function canOpenPlanningCalendar({ isAdmin, can, moduleSettings }) {
    return moduleSettings?.planning_calendar !== false && canViewPlanning({ isAdmin, can });
}
