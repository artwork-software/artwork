<template>
    <component :is="Comp" v-bind="$attrs" />
</template>

<script setup lang="ts">
import { computed, defineAsyncComponent } from 'vue'
import { IconTag } from '@tabler/icons-vue' // Fallback, sofort verfügbar

const props = defineProps<{ name?: string }>()
const cache = new Map<string, any>()

/**
 * Akzeptiert: "home", "home-2", "icon-home-2", "IconHome2"
 * → "IconHome2"
 */
function toTablerExportName(input?: string): string | null {
    if (!input) return null
    let s = input.trim()

    // "IconHome2" bleibt so
    if (/^Icon[A-Z0-9]/.test(s)) return s

    // "icon-home-2" → "home-2"
    s = s.replace(/^icon[-_]*/, '')

    // "home-2" → "Home2"
    const pascal = s
        .toLowerCase()
        .replace(/(^\w|[-_]\w)/g, m => m.replace(/[-_]/, '').toUpperCase())

    return `Icon${pascal}`
}

/** Lädt das Tabler-Icon-Modul genau EINMAL, wenn benötigt */
let tablerModPromise: Promise<any> | null = null
function loadTablerModule() {
    if (!tablerModPromise) {
        tablerModPromise = import('@tabler/icons-vue')
    }
    return tablerModPromise
}

/** Holt (und cached) die Icon-Komponente nach Name */
function resolveTablerIcon(name?: string) {
    const exportName = toTablerExportName(name)
    if (!exportName) return IconTag

    if (cache.has(exportName)) return cache.get(exportName)

    const Comp = defineAsyncComponent({
        loader: async () => {
            try {
                const mod = await loadTablerModule()
                return mod?.[exportName] ?? IconTag
            } catch {
                return IconTag
            }
        },
        delay: 0,
        timeout: 8000,
        onError(err, _retry, fail, attempts) {
            // nach 2 Fehlversuchen nicht weiter stressen
            if (attempts > 1) fail(err)
        }
    })

    cache.set(exportName, Comp)
    return Comp
}

const Comp = computed(() => resolveTablerIcon(props.name))
</script>
