import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';
import tailwindcss from "@tailwindcss/vite";
import viteCompression from 'vite-plugin-compression'
import Components from 'unplugin-vue-components/vite'

const port = 5173;
// DDEV_PRIMARY_URL includes the router port when it is non-standard (e.g. :8443).
// Use the portless DDEV URL for Vite, and let Vite infer the origin outside DDEV.
const ddevPrimaryUrl = process.env.DDEV_PRIMARY_URL_WITHOUT_PORT
    ?? process.env.DDEV_PRIMARY_URL?.replace(/:\d+$/, '');
const origin = ddevPrimaryUrl ? `${ddevPrimaryUrl}:${port}` : undefined;

// @tabler/icons-vue ist ein Barrel ueber 6092 Icon-Module. Schon ein einzelner Named Import
// zwingt Rollup, das ganze Barrel aufzuloesen — der Peak beim Graph-Aufbau lag dadurch bei
// ~2,5 GB und der Build starb auf kleineren Maschinen am OOM-Killer.
//
// Deshalb werden Barrel-Imports beim Build auf Deep-Pfade umgeschrieben:
//
//   import { IconCheck, IconX } from '@tabler/icons-vue'
//   -> import IconCheck from '@tabler/icons-vue/dist/esm/icons/IconCheck.mjs'; import IconX from ...
//
// Damit landen nur die tatsaechlich benutzten Icons im Modulgraph. Die Quelldateien bleiben
// unveraendert lesbar, und neue Imports greifen automatisch mit.
//
// Nur fuer den Build: der Dev-Server serviert Module ohnehin einzeln, dort gibt es weder ein
// Speicherproblem noch einen Grund, optimizeDeps und Sourcemaps anzufassen.
function tablerDeepImports() {
    const BARREL = /import\s*\{([^}]+)\}\s*from\s*['"]@tabler\/icons-vue['"]\s*;?/g
    const ANY_BARREL = /from\s*['"]@tabler\/icons-vue['"]/

    return {
        name: 'tabler-deep-imports',
        enforce: 'pre',
        apply: 'build',
        transform(code, id) {
            if (!/\.(vue|js|ts)$/.test(id.split('?')[0])) return
            if (!code.includes('@tabler/icons-vue')) return

            const out = code.replace(BARREL, (_match, names) =>
                names
                    .split(',')
                    .map(n => n.trim())
                    .filter(Boolean)
                    .map(n => `import ${n} from '@tabler/icons-vue/dist/esm/icons/${n}.mjs';`)
                    .join(' ')
            )

            // Kein stiller Rueckfall aufs Barrel: lieber der Build bricht, als dass wieder
            // 6092 Module in den Graph wandern.
            if (ANY_BARREL.test(out)) {
                this.error(
                    `tabler-deep-imports: Import aus '@tabler/icons-vue' in ${id} konnte nicht `
                    + `umgeschrieben werden. Nur "import { IconX, IconY } from '@tabler/icons-vue'" `
                    + `wird unterstuetzt — keine Aliase, Namespace- oder Default-Imports.`
                )
            }

            return out
        },
    }
}

export default defineConfig({
    // Frontend-Env kommt ausschliesslich zur Laufzeit ueber window.__APP_CONFIG__
    // (config/frontend.php -> app.blade.php). Kein VITE_-Wert darf ins Bundle, sonst
    // ist das Artefakt wieder an eine Umgebung gebunden und muss pro Kunde neu gebaut
    // werden. Dieser Prefix existiert nicht, Vite exponiert damit nichts mehr.
    envPrefix: 'ARTWORK_NEVER_EXPOSE_',
    build: {
        // for modern browsers / node versions — ESNext includes top-level await
        target: 'esnext',
        reportCompressedSize: false,
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
        tablerDeepImports(),
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
        }),
        tailwindcss(),
        viteCompression({ algorithm: 'brotliCompress', ext: '.br', deleteOriginFile: false }),
        viteCompression({ algorithm: 'gzip', ext: '.gz', deleteOriginFile: false })
    ],
});
