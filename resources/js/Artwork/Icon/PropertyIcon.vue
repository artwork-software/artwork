<template>
    <component :is="Comp" v-bind="$attrs" />
</template>

<script setup lang="ts">
import { computed, defineAsyncComponent, unref, type Component } from 'vue'

// name kann String | Component | Ref davon sein
const props = defineProps<{ name?: unknown }>()
const cache = new Map<string, Component>()

/** Ist es (vermutlich) eine Vue-Komponente? */
function isVueComponent(v: unknown): v is Component {
    return typeof v === 'object' || typeof v === 'function'
}

/**
 * Normalisiert verschiedene Schreibweisen:
 * "home", "home-2", "icon-home-2", "IconHome2" -> "IconHome2"
 */
function toTablerExportName(input: string): string {
    let s = input.trim()

    // "IconHome2" bleibt so
    if (/^Icon[A-Z0-9]/.test(s)) return s

    // "icon-home-2" -> "home-2"
    s = s.replace(/^icon[-_]*/i, '')

    // "home-2" -> "Home2"
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

/** Holt (und cached) die Icon-Komponente nach Name oder gibt direkt die übergebene Komponente zurück */
function resolveTablerIcon(nameLike: unknown): Component {
    const n = unref(nameLike) as any

    if (n == null) return IconTag

    // Wenn bereits eine Komponente übergeben wird: direkt verwenden
    if (isVueComponent(n)) return n as Component

    // Alles andere sicher in String wandeln
    const exportName = toTablerExportName(String(n))

    const cached = cache.get(exportName)
    if (cached) return cached

    const Comp = defineAsyncComponent({
        loader: async () => {
            try {
                const mod = await loadTablerModule()
                return mod?.[exportName] ?? 'IconTag'
            } catch {
                return 'IconTag'
            }
        },
        delay: 0,
        timeout: 8000,
        onError(err, _retry, fail, attempts) {
            // nach 2 Fehlversuchen abbrechen
            if (attempts > 1) fail(err)
        }
    })

    cache.set(exportName, Comp)
    return Comp
}

const Comp = computed<Component>(() => resolveTablerIcon(props.name))
</script>
