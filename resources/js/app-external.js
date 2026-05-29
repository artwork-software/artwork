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
import PrimeVue from 'primevue/config'
import Aura from '@primeuix/themes/aura'
import Tooltip from 'primevue/tooltip'

const initialLocale =
    localStorage.getItem('locale') ||
    document.documentElement.lang ||
    'de'

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

        const locale =
            localStorage.getItem('locale') ||
            document.documentElement.lang ||
            'de'

        const messages = await import(`../../lang/${locale}.json`)
        i18n.global.setLocaleMessage(locale, messages.default || messages)

        app.use(i18n)
        app.use(PrimeVue, {
            theme: { preset: Aura, options: { darkModeSelector: '.fake-dark-selector' } },
            ripple: true,
        })
        app.directive('tooltip', Tooltip)

        app.config.globalProperties.$updateLocale = (newLocale) => {
            i18n.global.locale.value = newLocale
            document.documentElement.lang = newLocale
            localStorage.setItem('locale', newLocale)
        }

        if (import.meta.env.DEV) app.config.performance = true
        app.mount(el)
    },
    progress: { color: '#3017AD', showSpinner: false, includeCss: true },
}).then(() => {
    let isHandlingExpiry = false

    router.on('invalid', (event) => {
        const status = event.detail.response?.status
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
