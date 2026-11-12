/**
 * Regeln für UserPopoverTooltip: Wann gehört ein Objekt zu einem echten User, und wann müssen die
 * Kontaktdaten über user.tooltip.info nachgeladen werden?
 *
 * Viele Payloads schicken Personen bewusst nur als Avatar (User::AVATAR_FIELDS: id, Name, Foto).
 * Der Tooltip erkennt solche schlanken Objekte selbst und lädt Position, E-Mail, Telefon usw. beim
 * Öffnen nach. So zeigt er überall dieselben Angaben, auch wenn eine Stelle `lazy-load` vergisst.
 */

const USER_TYPES = [undefined, null, 'user', 0]

/**
 * Freelancer und Dienstleister teilen sich den Id-Raum nicht mit Usern. Für sie darf nie über
 * user.tooltip.info nachgeladen werden, sonst erschiene eine fremde Person im Tooltip.
 */
export function isTooltipUserEntity(user) {
    if (!user || typeof user !== 'object') {
        return false
    }
    if (!USER_TYPES.includes(user.type)) {
        return false
    }
    if (typeof user.manager_type === 'string' && !user.manager_type.endsWith('\\User')) {
        return false
    }
    // Dienstleister-Arrays ohne type tragen provider_name statt Vor- und Nachname.
    if (user.provider_name !== undefined && user.first_name === undefined && user.last_name === undefined) {
        return false
    }

    return true
}

/**
 * Nachladen, wenn die Stelle es ausdrücklich verlangt (lazyLoad) oder der Payload weder E-Mail noch
 * Telefonnummer enthält, also nur Avatar-Felder mitbringt.
 */
export function needsTooltipDetails(user, lazyLoad = false) {
    if (!user?.id || !isTooltipUserEntity(user)) {
        return false
    }
    if (lazyLoad) {
        return true
    }

    return !Object.hasOwn(user, 'email') && !Object.hasOwn(user, 'phone_number')
}
