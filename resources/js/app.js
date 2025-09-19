// resources/js/app.js
import './bootstrap'
import '../css/app.css'
import '../css/global.css'

import { createApp, h } from 'vue'
import { createInertiaApp } from '@inertiajs/vue3'
import { createI18n } from 'vue-i18n'
import LaravelPermissionToVueJS from 'laravel-permission-to-vuejs'
import PrimeVue from 'primevue/config'
import Aura from '@primeuix/themes/aura'
import Tooltip from 'primevue/tooltip'

import en from '../../lang/en.json'
import de from '../../lang/de.json'


const i18n = createI18n({
    legacy: false,
    globalInjection: true,
    locale: localStorage.getItem('locale') || document.documentElement.lang || 'de',
    fallbackLocale: 'en',
    messages: { en, de },
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
    setup({el, App: InertiaRoot, props, plugin}) {
        const app = createApp({render: () => h(InertiaRoot, props)})
        app.use(plugin)
        if (typeof route !== 'undefined') app.mixin({methods: {route}})

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
}).then(r => {})
