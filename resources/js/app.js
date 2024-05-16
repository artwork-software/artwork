require('./bootstrap');
require('../css/global.css');

import { createApp, h } from 'vue';
import { createInertiaApp } from '@inertiajs/inertia-vue3';
import { InertiaProgress } from '@inertiajs/progress';
import VueTailwindDatepicker from 'vue-tailwind-datepicker'
import '@vuepic/vue-datepicker/dist/main.css'
import VueMathjax from 'vue-mathjax-next';
import * as VueI18n from 'vue-i18n'
import PrimeVue from 'primevue/config';

const svgColors = {
    eventType0:'#A7A6B1',
    eventType1:'#641A54',
    eventType2:'#DA3F87',
    eventType3:'#EB7A3D',
    eventType4:'#F1B640',
    eventType5:'#86C554',
    eventType6:'#2EAA63',
    eventType7:'#3DC3CB',
    eventType8:'#168FC3',
    eventType9:'#4D908E',
    eventType10:'#21485C'
}
const messages = {
    en: require('../../lang/en.json'),
    de: require('../../lang/de.json')
}

const i18n = VueI18n.createI18n({
    legacy: false, // you must specify 'legacy: false' option
    locale: document.documentElement.lang,
    fallbackLocale: 'en', // set fallback locale
    messages, // set locale messages
})

const appName = window.document.getElementsByTagName('title')[0]?.innerText || 'Laravel';



createInertiaApp({
    title: (title) => `${title}`,
    resolve: (name) => require(`./Pages/${name}.vue`),
    setup({ el, app: inertiaApp, props, plugin }) {
        const app = createApp({ render: () => h(inertiaApp, props) })
            .use(plugin)
            .mixin({ methods: { route }})
        app.config.globalProperties.$svgColors = svgColors;
        app.use(VueTailwindDatepicker);
        app.use(VueMathjax)
        app.use(i18n)
        app.use(PrimeVue, { unstyled: true })
        app.mount(el);
        app.config.globalProperties.$updateLocale = function (newLocale) {
            this.$i18n.locale = newLocale; // Für VueI18n 9.x und Vue 3
            document.documentElement.lang = newLocale;
        };
    },
});


InertiaProgress.init({ color: '#3017AD', showSpinner: true, includeCSS: true });

