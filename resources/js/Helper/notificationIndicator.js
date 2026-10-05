import { ref } from 'vue'

/**
 * Neue Benachrichtigungen seit dem letzten Besuch des Centers, die live eingetroffen sind
 * (Seitenwert: auth.user.show_notification_indicator). Gemeinsam für Toast und Glocke.
 */
export const liveNotificationArrived = ref(false)

export const markNotificationArrived = () => {
    liveNotificationArrived.value = true
}

/** Beim Besuch des Benachrichtigungscenters – sonst kam der Punkt auf anderen Seiten zurück */
export const resetNotificationArrived = () => {
    liveNotificationArrived.value = false
}

/**
 * Live-Hinweise ohne Eintrag im Center (z. B. Rückmeldung an die handelnde Person) setzen keinen
 * Glocken-Punkt; ältere Payloads ohne Kennzeichen zählen weiter als Eintrag.
 */
export const createsNotificationEntry = (message) => message?.delivered !== false

export const hasUnseenNotifications = (user, isOnNotificationPage) =>
    !isOnNotificationPage && (Boolean(user?.show_notification_indicator) || liveNotificationArrived.value)
