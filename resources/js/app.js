// resources/js/app.js
import { createInstanceFormatter } from '@/Helper/instanceFormat.js'
import './bootstrap'
import '../css/app.css'
import '../css/global.css'

import { createApp, h } from 'vue'
import { createInertiaApp, router, usePage } from '@inertiajs/vue3'
import { createI18n } from 'vue-i18n'
import LaravelPermissionToVueJS from 'laravel-permission-to-vuejs'
import PrimeVue from 'primevue/config'
import Aura from '@primeuix/themes/aura'
import Tooltip from 'primevue/tooltip'
import {messageForFailedRequest, setAppToastTranslator, showAppToast, t} from './Helper/appToast'
import { hasInlineFeedback } from './Helper/sequentialUpload.js'

async function loadLocaleMessages(locale) {
    // Vite macht daraus separate Chunks pro Sprache
    const messages = await import(`../../lang/${locale}.json`)
    return messages.default || messages
}

const initialLocale =
    localStorage.getItem('locale') ||
    document.documentElement.lang ||
    'de'

const i18n = createI18n({
    legacy: false,
    globalInjection: true,
    locale: initialLocale,
    fallbackLocale: 'en',
    messages: {}, // erstmal leer
    missingWarn: false,
    fallbackWarn: false,
    missing: (_l, key) => key,
})

const pages = import.meta.glob('./Pages/**/*.vue')


createInertiaApp({
    title: (title) => `${title}`,
    resolve: (name) => {
        const page = pages[`./Pages/${name}.vue`]
        if (!page) throw new Error(`Page not found: ${name}`)
        return page()
    },
    async setup({el, App: InertiaRoot, props, plugin}) {
        const app = createApp({render: () => h(InertiaRoot, props)})
        app.use(plugin)

        if(typeof route !== 'undefined')
        {
            app.mixin({methods: {route}})
        }

        // Sprache dynamisch laden, bevor wir i18n registrieren
        const initialLocale =
            localStorage.getItem('locale') ||
            document.documentElement.lang ||
            'de'

        const messages = await import(`../../lang/${initialLocale}.json`)
        i18n.global.setLocaleMessage(initialLocale, messages.default || messages)

        app.use(i18n)
        setAppToastTranslator((key) => i18n.global.t(key))
        // Kurzmeldungen aus Komponenten: this.$toast.error(...) / .success(...)
        app.config.globalProperties.$toast = {
            success: (message) => showAppToast('success', message),
            error: (message) => showAppToast('error', message),
        }
        app.use(PrimeVue, {
            theme: {preset: Aura, options: {darkModeSelector: '.fake-dark-selector'}},
            ripple: true,
        })
        app.directive('tooltip', Tooltip)
        //app.use(VueMathjax)
        app.use(LaravelPermissionToVueJS)


        // Regionale Formate der Instanz in Templates: {{ $currencySymbol() }}, {{ $formatCurrency(x) }}
        const instanceFormatter = () => createInstanceFormatter(usePage()?.props?.instanceFormat)
        app.config.globalProperties.$currencySymbol = () => instanceFormatter().currencySymbol
        app.config.globalProperties.$formatCurrency = (value) => instanceFormatter().formatCurrency(value)

        app.config.globalProperties.$updateLocale = (newLocale) => {
            i18n.global.locale.value = newLocale
            document.documentElement.lang = newLocale
            localStorage.setItem('locale', newLocale)
        }

        if (import.meta.env.DEV) app.config.performance = true
        app.mount(el)
    },
    progress: {color: '#276293', showSpinner: false, includeCss: true},
}).then(() => {
    // Session-Expiry abfangen: Wenn das Backend eine nicht-Inertia-Antwort
    // zurückgibt (z.B. 401, 419, oder Redirect zu /oauth/authorize),
    // dem User eine verständliche Meldung zeigen statt eines Fehlerscreens.
    router.on('invalid', (event) => {
        event.preventDefault()

        const status = event.detail.response?.status
        if (status === 401 || status === 419) {
            alert(t('Your session has expired. The page will reload so you can sign in again.'))
            window.location.reload()
        } else if (status === 409) {
            // Echter Konflikt (z.B. Raumanfrage wurde parallel bereits beantwortet).
            // Kein Session-Problem — Inertia-Versions-409er tragen X-Inertia-Location
            // und werden von Inertia selbst behandelt, bevor 'invalid' feuert.
            alert(t('The action could not be completed because the data has changed in the meantime. The page will reload.'))
            window.location.reload()
        } else if (hasInlineFeedback(event.detail.response)) {
            // Upload-Modals (submitInertiaForm) zeigen 413/403/5xx selbst an – sonst käme je Datei ein alert/Toast
            return
        } else if (status === 413) {
            // Server (nginx client_max_body_size / PHP post_max_size) hat den
            // Request abgelehnt, weil die Dateien zusammen zu groß sind.
            alert(t('The uploaded files are too large for the server in total. Please upload fewer or smaller files at once.'))
        } else {
            // 403/404/5xx: vorher stillschweigend verworfen – das Modal blieb offen,
            // ohne Hinweis, dass nichts gespeichert wurde.
            const message = messageForFailedRequest(status)
            if (message) {
                showAppToast('error', t(message))
            }
        }
    })
})
