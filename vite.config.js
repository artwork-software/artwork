import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';
import tailwindcss from "@tailwindcss/vite";
import viteCompression from 'vite-plugin-compression'
import Components from 'unplugin-vue-components/vite'
import Icons from 'unplugin-icons/vite'
import IconsResolver from 'unplugin-icons/resolver'

const port = 5173;
// DDEV_PRIMARY_URL includes the router port when it is non-standard (e.g. :8443).
// Use the portless DDEV URL for Vite, and let Vite infer the origin outside DDEV.
const ddevPrimaryUrl = process.env.DDEV_PRIMARY_URL_WITHOUT_PORT
    ?? process.env.DDEV_PRIMARY_URL?.replace(/:\d+$/, '');
const origin = ddevPrimaryUrl ? `${ddevPrimaryUrl}:${port}` : undefined;

export default defineConfig({
    build: {
        // for modern browsers / node versions — ESNext includes top-level await
        target: 'esnext',
        rollupOptions: {
            output: {
                manualChunks(id) {
                    // Bundle all Tabler icons into one chunk instead of ~130 tiny
                    // per-icon files (HTTP/1.1 connection-limit queueing on prod).
                    if (id.includes('@tabler/icons-vue')) {
                        return 'tabler-icons';
                    }
                },
            },
        },
    },
    // you can also tweak esbuildOptions directly:
    esbuild: {
        target: 'esnext',
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
            input: [
                'resources/js/app.js',
                'resources/js/app-external.js',
            ],
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
        Components({
            dts: 'resources/types/components.d.ts',
            resolvers: [IconsResolver({ prefix: 'i', enabledCollections: ['tabler'] })],
        }),
        Icons({ compiler: 'vue3', autoInstall: true }),
        tailwindcss(),
        viteCompression({ algorithm: 'brotliCompress', ext: '.br', deleteOriginFile: false }),
        viteCompression({ algorithm: 'gzip', ext: '.gz', deleteOriginFile: false })
    ],
});
