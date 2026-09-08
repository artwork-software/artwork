<template>
    <Head>
        <link rel="icon" type="image/png" :href="usePage().props.small_logo" />
        <title>{{ title }} - {{ usePage().props.page_title }}</title>
    </Head>
    <div class="artwork relative">
        <div v-if="pushNotifications.length > 0" class="absolute top-16 right-5">
            <div v-for="pushNotification in pushNotifications" :key="pushNotification.id" :id="pushNotification.id"
                 class="my-2 z-50 flex relative w-full max-w-xs rounded-lg border border-border-subtle bg-surface shadow-overlay"
                 role="alert">
                <div class="flex p-4">
                    <div class="inline-flex flex-shrink-0 justify-center items-center rounded-lg">
                        <img alt="Notification" v-if="pushNotification.type === 'success'"
                             class="h-9 w-9" src="/Svgs/IconSvgs/icon_push_notification_green.svg"/>
                        <img alt="Notification" v-if="pushNotification.type === 'error'" class="h-9 w-9"
                             src="/Svgs/IconSvgs/icon_push_notification_red.svg"/>
                    </div>
                    <div class="ml-4 text-sm font-semibold text-text">{{ pushNotification.message }}</div>
                </div>
                <button type="button" class="-mt-4 mr-2">
                    <PropertyIcon name="IconX" class="-mt-4 h-5 w-5 text-text-subtle hover:text-danger relative"
                           @click="closePushNotification(pushNotification.id)"/>
                </button>
            </div>
        </div>


        <!-- Globaler Flash-Toast (page.props.flash.success/error): zeigt Backend-Rückmeldungen nach
             Redirects (z.B. „12 Schichten angelegt", Festschreibung, Freigabe-Anfrage) ohne dass jede
             Seite Flash selbst rendern muss. Seiten, die Flash bereits als Modal/Banner darstellen,
             sind in FLASH_HANDLED_BY_PAGE ausgenommen; Objekt-Flashes (z.B. success.shift_qualification)
             werden nie als Toast gezeigt. -->
        <div
            v-if="flashToasts.length > 0"
            aria-live="polite"
            class="pointer-events-none fixed inset-x-0 top-16 z-[120] flex flex-col items-end gap-2 px-5"
        >
            <div
                v-for="toast in flashToasts"
                :key="toast.id"
                role="status"
                class="pointer-events-auto flex w-full max-w-sm items-start gap-3 rounded-lg border border-border-subtle bg-surface p-4 shadow-overlay"
            >
                <PropertyIcon
                    :name="toast.type === 'error' ? 'IconAlertCircle' : 'IconCircleCheck'"
                    class="size-5 shrink-0 mt-0.5"
                    :class="toast.type === 'error' ? 'text-danger' : 'text-success'"
                    :stroke-width="1.5"
                    aria-hidden="true"
                />
                <p class="min-w-0 flex-1 text-sm text-text break-words">{{ toast.message }}</p>
                <button
                    type="button"
                    class="shrink-0 rounded-md text-text-subtle hover:text-text"
                    @click="dismissFlashToast(toast.id)"
                >
                    <span class="sr-only">{{ $t('Close') }}</span>
                    <PropertyIcon name="IconX" class="size-5" :stroke-width="1.5" aria-hidden="true"/>
                </button>
            </div>
        </div>

        <SubMenu />

        <main class="lg:pl-20 xl:pl-20 pb-20 relative isolate z-0">
            <div class="artwork relative" id="main-content-wrapper">
                <slot></slot>
            </div>
        </main>

        <PopupChat v-if="$page.props.auth.user.use_chat"/>
    </div>
</template>

<script>
import {ref as moduleRef} from "vue";
// Modul-Zustand (überlebt Remounts des Layouts bei Seitenwechseln): laufende Flash-Toasts + Dedupe
const flashToasts = moduleRef([])
let flashToastSeq = 0
const flashDedupe = {key: null, at: 0}
</script>

<script setup>
import {Head, router, usePage} from "@inertiajs/vue3"
import {defineAsyncComponent, onBeforeMount, onMounted, onUnmounted, ref, watchEffect} from "vue";
import {reloadRolesAndPermissions} from "laravel-permission-to-vuejs";
import {useI18n} from "vue-i18n";
import PopupChat from "@/Components/Chat/PopupChat.vue";
import PropertyIcon from "@/Artwork/Icon/PropertyIcon.vue";
import SubMenu from "@/Layouts/SubMenu.vue";
const { locale } = useI18n();

const props = defineProps({
    title: {
        type: String,
        default: 'Dashboard'
    },
})

watchEffect(() => {
    window.Laravel = window.Laravel || {}
    if (usePage().props?.permissions) {
        window.Laravel.jsPermissions = usePage().props.permissions;
    }
})

const pushNotifications = ref([])

// --- Globaler Flash-Toast ---------------------------------------------------------------
// Seiten, die page.props.flash selbst als Modal/Banner rendern → kein Doppel-Toast.
const FLASH_HANDLED_BY_PAGE = {
    'Settings/ShiftSettings': ['success', 'error'],
    'Branding/Index': ['success'],
    'ModuleSettings/Index': ['success'],
    'System/FileSettings/Index': ['success'],
    'ExternalUserManagement/Index': ['success'],
    'CommunicationAndLegal/Index': ['success'],
    'BudgetSettingsGeneral/Index': ['success', 'error'],
    'BudgetSettingsAccountManagement/Index': ['success', 'error'],
    'BudgetSettingsTemplates/Index': ['error'],
    'BudgetSettingsTemplates/TrashIndex': ['error'],
    'Projects/Show': ['error'],
    'Interfaces/Sage/SageApiSettings': ['success', 'error'],
    'PermissionPresets/Index': ['success', 'error'],
    'CRM/Duplicates': ['success'],
}

const dismissFlashToast = (id) => {
    const toast = flashToasts.value.find((t) => t.id === id)
    if (toast?.timeoutId) clearTimeout(toast.timeoutId)
    flashToasts.value = flashToasts.value.filter((t) => t.id !== id)
}

const pushFlashToast = (type, message) => {
    const id = `flash-toast-${++flashToastSeq}`
    const timeoutId = setTimeout(() => dismissFlashToast(id), type === 'error' ? 8000 : 5000)
    flashToasts.value = [...flashToasts.value, {id, type, message, timeoutId}]
}

/** Flash-Strings der übergebenen Inertia-Page als Toast anzeigen (nur Strings, nur nicht-selbstrendernde Seiten). */
const showFlashFromPage = (pageData) => {
    const flash = pageData?.props?.flash
    if (!flash) return
    // Dedupe: onMounted (Vollaufruf/Remount) und router 'success' sehen dieselbe Page → nur einmal
    const key = `${pageData?.component}|${pageData?.url}|${flash.success ?? ''}|${flash.error ?? ''}`
    const now = Date.now()
    if (key === flashDedupe.key && now - flashDedupe.at < 1500) return
    flashDedupe.key = key
    flashDedupe.at = now
    const handled = FLASH_HANDLED_BY_PAGE[pageData?.component] ?? []
    for (const type of ['success', 'error']) {
        const message = flash[type]
        if (typeof message !== 'string' || message.trim() === '' || handled.includes(type)) continue
        pushFlashToast(type, message)
    }
}

// Nach jeder erfolgreichen Inertia-Navigation (inkl. redirect()->back() nach Formularen)
const removeFlashListener = router.on('success', (event) => showFlashFromPage(event.detail.page))

onUnmounted(() => removeFlashListener())

const closePushNotification = (id) => {
    const pushNotification = document.getElementById(id);
    pushNotification?.remove();
}

onBeforeMount(() => {
    /**
     * i think this is unnecessary, but it is here to ensure that the calendar settings are set correctly

    if ( (route().current('events') === false && route().current('shifts.plan') === false) && usePage().props.auth.user.calendar_settings.use_project_time_period){
        let desiredRoute = route('user.calendar_settings.toggle_calendar_settings_use_project_period');
        let payload = {
            use_project_time_period: false,
            project_id: 0,
            is_axios: true
        };

        axios.patch(desiredRoute, payload);
    }
     */
    reloadRolesAndPermissions()
})

onMounted(() => {
    // Vollständiger Seitenaufruf nach Redirect (kein Inertia-'success'-Event): Flash einmalig zeigen
    showFlashFromPage(usePage())
    document.documentElement.lang = usePage().props.auth.user.language
    locale.value = usePage().props.auth.user.language
    window.Echo.private(`notifications.${usePage().props.auth.user.id}`)
        .listen('.incoming-notification', (notification) => {
            pushNotifications.value.push(notification.message);
            setTimeout(() => {
                closePushNotification(notification.message.id)
            }, 3000)
        });


})

</script>
