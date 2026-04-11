// resources/js/app.js
import './bootstrap'
import '../css/app.css'
import '../css/global.css'

import { createApp, h } from 'vue'
import { createInertiaApp, router } from '@inertiajs/vue3'
import { createI18n } from 'vue-i18n'
import LaravelPermissionToVueJS from 'laravel-permission-to-vuejs'
import PrimeVue from 'primevue/config'
import Aura from '@primeuix/themes/aura'
import Tooltip from 'primevue/tooltip'

import * as pdfjs from 'pdfjs-dist'

pdfjs.GlobalWorkerOptions.workerSrc = new URL(
    'pdfjs-dist/build/pdf.worker.min.mjs',
    import.meta.url
).toString()

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
        app.use(PrimeVue, {
            theme: {preset: Aura, options: {darkModeSelector: '.fake-dark-selector'}},
            ripple: true,
        })
        app.directive('tooltip', Tooltip)
        //app.use(VueMathjax)
        app.use(LaravelPermissionToVueJS)


        app.config.globalProperties.$updateLocale = (newLocale) => {
            i18n.global.locale.value = newLocale
            document.documentElement.lang = newLocale
            localStorage.setItem('locale', newLocale)
        }

        if (import.meta.env.DEV) app.config.performance = true
        app.mount(el)
    },
    progress: {color: '#3017AD', showSpinner: false, includeCss: true},
}).then(() => {
    // Session-Expiry abfangen: Wenn das Backend eine nicht-Inertia-Antwort
    // zurückgibt (z.B. 401, 419, oder Redirect zu /oauth/authorize),
    // dem User eine verständliche Meldung zeigen statt eines Fehlerscreens.
    router.on('invalid', (event) => {
        event.preventDefault()

        const status = event.detail.response?.status
        if (status === 401 || status === 419 || status === 409) {
            alert('Deine Sitzung ist abgelaufen. Die Seite wird neu geladen, damit du dich wieder einloggen kannst.')
            window.location.reload()
        }
    })
})
