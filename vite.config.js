import { spawnSync } from 'node:child_process';
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

// Legt die Tabler-SVGs nach dem Bundle-Schreiben in den outDir (public/build/icons/tabler).
// Als Build-Hook statt npm-Script-Kette, damit auch ein direktes "vite build" (z.B. im
// Docker-Build) die Icons erzeugt. closeBundle laeuft nach dem Leeren von outDir.
function tablerIconsSync() {
    return {
        name: 'tabler-icons-sync',
        apply: 'build',
        closeBundle() {
            const result = spawnSync(process.execPath, ['scripts/sync-tabler-icons.mjs'], { stdio: 'inherit' })
            if (result.status !== 0) {
                throw new Error('tabler-icons-sync: scripts/sync-tabler-icons.mjs fehlgeschlagen')
            }
        },
    }
}

export default defineConfig({
    envPrefix: 'ARTWORK_NEVER_EXPOSE_',
    build: {
        // for modern browsers / node versions — ESNext includes top-level await
        target: 'esnext',
        reportCompressedSize: false,
    },
    esbuild: {
        target: 'esnext',
    },
    server: {
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
        viteCompression({ algorithm: 'gzip', ext: '.gz', deleteOriginFile: false }),
        tablerIconsSync()
    ],
});
