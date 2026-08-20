<template>
    <component :is="Comp" v-bind="$attrs" />
</template>

<script setup lang="ts">
import { computed, unref, type Component } from 'vue'
import { iconComponent } from '@/Composeables/useTablerIcon'

// name kann String | Component | Ref davon sein
const props = defineProps<{ name?: unknown }>()

/** Ist es (vermutlich) eine Vue-Komponente? */
function isVueComponent(v: unknown): v is Component {
    return typeof v === 'object' || typeof v === 'function'
}

// Ein String wird ueber das Composable zu einem SVG aus public/build/icons/tabler/ aufgeloest —
// bewusst ohne Bundler-Beteiligung, damit nicht alle 6092 Tabler-Module im Modulgraph landen.
// Wird stattdessen direkt eine Komponente durchgereicht (z.B. ein statisch importiertes
// Tabler-Icon aus ToolTipComponent), bleibt die unveraendert.
const Comp = computed<Component>(() => {
    const n = unref(props.name)

    if (n != null && isVueComponent(n)) return n as Component

    return iconComponent(n)
})
</script>
