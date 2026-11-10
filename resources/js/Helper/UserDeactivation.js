/**
 * Deaktivierte Accounts (users.deactivated_at gesetzt): Login gesperrt, Zuordnungen bleiben sichtbar.
 * Avatare werden grau dargestellt, der Tooltip zeigt „Account inaktiv“ statt der E-Mail-Adresse.
 * Freelancer/Dienstleister haben das Feld nicht und gelten immer als aktiv.
 */
export function isUserDeactivated(user) {
    return !!user && user.deactivated_at !== undefined && user.deactivated_at !== null && user.deactivated_at !== ''
}

/** Klassen für den Avatar deaktivierter Accounts: grauer Ring statt farbigem, Bild entsättigt. */
export const DEACTIVATED_AVATAR_CLASSES = 'grayscale opacity-70 ring-2 ring-border-strong'

export function deactivatedAvatarClasses(user) {
    return isUserDeactivated(user) ? DEACTIVATED_AVATAR_CLASSES : ''
}

/**
 * Darf die angemeldete Person diesen Account deaktivieren bzw. reaktivieren? Spiegel von
 * UserPolicy::deactivate (das Backend prüft erneut): Personalverwaltung oder Admin, Admin-Accounts nur
 * durch Admins, nie der eigene Account, nur echte User (keine Freelancer/Dienstleister).
 */
export function canChangeAccountActivation({ user, authUserId, isAdmin, canManageWorkers, isTargetAdmin }) {
    if (!user || (user.type !== undefined && user.type !== 'user') || user.id === authUserId) {
        return false
    }
    if (!isAdmin && !canManageWorkers) {
        return false
    }

    return isAdmin || !isTargetAdmin
}
