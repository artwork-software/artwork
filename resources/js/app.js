import './bootstrap';
import '../css/app.css';
import '../css/global.css';

import {createApp, h} from 'vue';
import {createInertiaApp} from '@inertiajs/vue3';
import LaravelPermissionToVueJS from 'laravel-permission-to-vuejs'
import VueMathjax from 'vue-mathjax-next';
import * as VueI18n from 'vue-i18n';

import en from '../../lang/en.json';
import de from '../../lang/de.json';
import Icons from "@/icons.js";
import PrimeVue from 'primevue/config';
import Aura from '@primeuix/themes/aura';
import Tooltip from 'primevue/tooltip';

const messages = {
    en: en,
    de: de
};


const i18n = VueI18n.createI18n({
    legacy: false, // Verwende die Composition API
    locale: document.documentElement.lang || 'de', // Standard-Sprache
    fallbackLocale: 'en', // Fallback-Sprache
    messages,
    missingWarn: false, // Deaktiviert Warnungen für fehlende Schlüssel
    fallbackWarn: false, // Deaktiviert Warnungen für Fallback-Schlüssel
    missing: (locale, key) => {
        // Gibt den Schlüssel zurück, wenn keine Übersetzung gefunden wurde
        return key;
    },
});
const pages = import.meta.glob('./Pages/**/*.vue');

createInertiaApp({
    title: (title) => `${title}`,
    resolve: (name) => {
        const pages = import.meta.glob('./Pages/**/*.vue')
        return pages[`./Pages/${name}.vue`]();
    },
    setup({ el, App: inertiaApp, props, plugin }) {
        const app = createApp({ render: () => h(inertiaApp, props) })
            .use(plugin)
            .mixin({ methods: { route }});
        app.use(VueMathjax);
        app.use(i18n);
        app.use(Icons);
        app.use(PrimeVue, {
            theme: {
                preset: Aura,
                options: {
                    darkModeSelector: '.fake-dark-selector', // trying to also force a non-usage of the dark mode
                },
            },
        });
        app.use(LaravelPermissionToVueJS);
        app.directive('tooltip', Tooltip);
        app.mount(el);
        app.config.globalProperties.$updateLocale = function (newLocale) {
            this.$i18n.locale = newLocale;
            document.documentElement.lang = newLocale;
        };
    },
    progress: {
        color: '#3017AD',
        showSpinner: true,
        includeCss: true,
    },
});
