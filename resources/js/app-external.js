// resources/js/app-external.js
//
// Eigener Vite-Entry-Point fuer externen Zugriff. Bewusst getrennt vom internen
// app.js, damit kein internes Permission-/Echo-/Broadcast-Setup ins externe
// Bundle landet (Trust-Boundary).
//
// NICHT importieren / verwenden:
//   - laravel-permission-to-vuejs (Permissions/Rollen)
//   - reloadRolesAndPermissions
//   - window.Echo / Pusher / Reverb
//   - bootstrap.js (initialisiert Echo)

import './bootstrap-external'
import '../css/app.css'
import '../css/global.css'

import { createApp, h } from 'vue'
import { createInertiaApp, router } from '@inertiajs/vue3'
import { createI18n } from 'vue-i18n'
import { createLocaleSwitcher, resolveInitialLocale } from '@/Helper/appLocale.js'
import PrimeVue from 'primevue/config'
import Aura from '@primeuix/themes/aura'
import Tooltip from 'primevue/tooltip'

const initialLocale = resolveInitialLocale(document.documentElement.lang)

const i18n = createI18n({
    legacy: false,
    globalInjection: true,
    locale: initialLocale,
    fallbackLocale: 'en',
    messages: {},
    missingWarn: false,
    fallbackWarn: false,
    missing: (_l, key) => key,
})

async function loadLocaleMessages(locale) {
    const messages = await import(`../../lang/${locale}.json`)
    return messages.default || messages
}

const pages = import.meta.glob('./Pages/ExternalAccess/**/*.vue')

createInertiaApp({
    title: (title) => `${title}`,
    resolve: (name) => {
        const page = pages[`./Pages/ExternalAccess/${name}.vue`]
        if (!page) {
            throw new Error(`External page not found: ${name}`)
        }
        return page()
    },
    async setup({ el, App: InertiaRoot, props, plugin }) {
        const app = createApp({ render: () => h(InertiaRoot, props) })
        app.use(plugin)

        if (typeof route !== 'undefined') {
            app.mixin({ methods: { route } })
        }

        i18n.global.setLocaleMessage(initialLocale, await loadLocaleMessages(initialLocale))

        app.use(i18n)
        app.use(PrimeVue, {
            theme: { preset: Aura, options: { darkModeSelector: '.fake-dark-selector' } },
            ripple: true,
        })
        app.directive('tooltip', Tooltip)

        app.config.globalProperties.$updateLocale = createLocaleSwitcher(i18n, loadLocaleMessages, document.documentElement)

        if (import.meta.env.DEV) app.config.performance = true
        app.mount(el)
    },
    progress: { color: '#276293', showSpinner: false, includeCss: true },
}).then(() => {
    let isHandlingExpiry = false

    router.on('invalid', (event) => {
        const status = event.detail.response?.status

        // 403 auf einer Tab-Route: der Gesamt-Zugang ist noch gueltig, aber der
        // Tab-Scope ist abgelaufen/entzogen. Zurueck aufs Dashboard (zeigt die
        // verbleibenden freigegebenen Tabs). Die guest-only Access-Expired-Seite
        // wuerde authentifizierte Externe ohnehin aufs Dashboard umleiten.
        if (status === 403) {
            event.preventDefault()
            if (isHandlingExpiry) return
            isHandlingExpiry = true
            window.location.href = '/external/dashboard'
            return
        }

        if (status !== 401 && status !== 419 && status !== 409) {
            return
        }

        event.preventDefault()

        if (isHandlingExpiry) return
        isHandlingExpiry = true

        // Externe haben kein Reload-Loop-Recovery, daher direkter Redirect.
        window.location.href = '/external/login?expired=1'
    })
})
