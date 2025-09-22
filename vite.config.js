import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';
import tailwindcss from "@tailwindcss/vite";
import viteCompression from 'vite-plugin-compression'
import Components from 'unplugin-vue-components/vite'
import Icons from 'unplugin-icons/vite'
import IconsResolver from 'unplugin-icons/resolver'

const port = 5173;
const origin = `${process.env.DDEV_PRIMARY_URL}:${port}`;

export default defineConfig({
    build: {
        // for modern browsers / node versions — ESNext includes top-level await
        target: 'esnext',
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
            input: ['resources/js/app.js'],
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
