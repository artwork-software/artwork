/**
 * Reine Hilfsfunktionen der Benachrichtigungseinstellungen (testbar ohne Vue).
 */

/** 'all' | 'some' | 'none' – Zustand eines Kanals (enabled_email/enabled_push) in einer Gruppe */
export const groupChannelState = (settings, channel) => {
    const enabled = settings.filter((setting) => setting[channel]).length
    if (enabled === 0) {
        return 'none'
    }

    return enabled === settings.length ? 'all' : 'some'
}

/** Gruppen auf Typen eingrenzen, deren (übersetzter) Titel oder Beschreibung den Suchtext enthält */
export const filterNotificationGroups = (groups, query, translate = (text) => text) => {
    const needle = String(query ?? '').trim().toLowerCase()
    if (needle === '') {
        return groups
    }

    return groups
        .map((group) => ({
            ...group,
            settings: group.settings.filter((setting) =>
                [translate(setting.title), translate(setting.description), translate(group.title)]
                    .some((text) => String(text).toLowerCase().includes(needle))
            ),
        }))
        .filter((group) => group.settings.length > 0)
}
