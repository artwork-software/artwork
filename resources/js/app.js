require('./bootstrap');

import { createApp, h } from 'vue';
import { createInertiaApp } from '@inertiajs/inertia-vue3';
import { InertiaProgress } from '@inertiajs/progress';
import 'flowbite';
import '@vuepic/vue-datepicker/dist/main.css'

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
const appName = window.document.getElementsByTagName('title')[0]?.innerText || 'Laravel';

createInertiaApp({
    title: (title) => `${title} - ${appName}`,
    resolve: (name) => require(`./Pages/${name}.vue`),
    setup({ el, app: inertiaApp, props, plugin }) {
        const app = createApp({ render: () => h(inertiaApp, props) })
            .use(plugin)
            .mixin({ methods: { route } })
        app.config.globalProperties.$svgColors = svgColors;
        app.mount(el);
    },
});


InertiaProgress.init({ color: '#4B5563' });

