import './bootstrap';
import '../css/app.scss';
import '../css/global.css';

import {createApp, h} from 'vue';
import {createInertiaApp} from '@inertiajs/vue3';
import VueTailwindDatepicker from 'vue-tailwind-datepicker';
import VueMathjax from 'vue-mathjax-next';
import * as VueI18n from 'vue-i18n';

import en from '../../lang/en.json';
import de from '../../lang/de.json';

const svgColors = {
    eventType0: '#A7A6B1',
    eventType1: '#641A54',
    eventType2: '#DA3F87',
    eventType3: '#EB7A3D',
    eventType4: '#F1B640',
    eventType5: '#86C554',
    eventType6: '#2EAA63',
    eventType7: '#3DC3CB',
    eventType8: '#168FC3',
    eventType9: '#4D908E',
    eventType10: '#21485C'
};

const messages = {
    en: en,
    de: de
};

const i18n = VueI18n.createI18n({
    legacy: false,
    locale: document.documentElement.lang,
    fallbackLocale: 'en',
    messages,
});

const pages = import.meta.glob('./Pages/**/*.vue');

createInertiaApp({
    title: (title) => `${title}`,
    resolve: async (name) => {
        const pagePath = `./Pages/${name}.vue`;
        const { default: component } = await pages[pagePath]();
        return component;
    },
    setup({ el, App: inertiaApp, props, plugin }) {
        const app = createApp({ render: () => h(inertiaApp, props) })
            .use(plugin)
            .mixin({ methods: { route }});

        app.config.globalProperties.$svgColors = svgColors;
        app.use(VueTailwindDatepicker);
        app.use(VueMathjax);
        app.use(i18n);

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
