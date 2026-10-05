import { ref } from 'vue'

/**
 * Neue Benachrichtigungen seit dem letzten Besuch des Centers, die live eingetroffen sind
 * (Seitenwert: auth.user.show_notification_indicator). Gemeinsam für Toast und Glocke.
 */
export const liveNotificationArrived = ref(false)

export const markNotificationArrived = () => {
    liveNotificationArrived.value = true
}

export const hasUnseenNotifications = (user, isOnNotificationPage) =>
    !isOnNotificationPage && (Boolean(user?.show_notification_indicator) || liveNotificationArrived.value)
