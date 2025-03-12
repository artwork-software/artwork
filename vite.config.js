import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';

const port = 5173;
const origin = `${process.env.DDEV_PRIMARY_URL}:${port}`;

export default defineConfig({
    ssr: {
        noExternal: [
            "vue3-colorpicker",
            "@aesoper/normal-utils",
            "vue-cal",
            "headlessui-float/vue",
            "headlessui/vue",
            "vue-tailwind-datepicker",
            "vuedraggable",
            "ColorPicker",
            "vue"
        ]
    },
    server: {
        // respond to all network requests
        host: '0.0.0.0',
        port: port,
        strictPort: true,
        // Defines the origin of the generated asset URLs during development,
        // this will also be used for the public/hot file (Vite devserver URL)
        cors: true,
        origin: origin,
        watch: {
            //still knows about the changes in storage dir but is not watch reloading because of them
            ignored: ['storage/*']
        }
    },
    plugins: [
        laravel({
            input: ['resources/js/app.js'],
            ssr: 'resources/js/ssr.js',
            refresh: true,
        }),
        vue({
            template: {
                transformAssetUrls: {
                    base: null,
                    includeAbsolute: false,
                },
            },
        }),
    ],
});
