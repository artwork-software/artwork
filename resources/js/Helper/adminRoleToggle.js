/**
 * Welche Rückfrage braucht ein Klick auf den Admin-Schalter im Rechte-Tab?
 * Vergeben und Entziehen fragen beide nach; beim eigenen Konto deutlicher,
 * weil man sich damit selbst aus dem Rechte-Tab aussperrt.
 *
 * @param {{checked: boolean, targetUserId: number|null|undefined, authUserId: number|null|undefined}} input
 * @returns {'grant'|'revoke'|'revoke-self'}
 */
export function adminToggleConfirmation({checked, targetUserId, authUserId}) {
    if (checked) {
        return 'grant'
    }

    const isOwnAccount = targetUserId != null && authUserId != null && Number(targetUserId) === Number(authUserId)

    return isOwnAccount ? 'revoke-self' : 'revoke'
}
