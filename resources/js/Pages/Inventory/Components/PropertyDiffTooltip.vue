<template>
    <span
        ref="trigger"
        class="underline decoration-dotted underline-offset-2 cursor-help"
        @mouseenter="show"
        @mouseleave="hide"
        @click.stop="toggle"
    >
        <slot>{{ label }}</slot>

        <Teleport to="body">
            <div
                v-if="visible && values.length"
                class="fixed z-[10000] max-w-[16rem] rounded-md bg-black/90 px-2.5 py-2 text-xs leading-snug text-white shadow-lg pointer-events-none font-lexend"
                :style="tooltipStyle"
            >
                <div v-if="heading" class="mb-1 font-semibold text-white/70">{{ heading }}</div>
                <ul class="space-y-0.5">
                    <li v-for="(value, index) in values" :key="index" class="whitespace-nowrap">
                        {{ value }}
                    </li>
                </ul>
                <div class="absolute left-1/2 top-full h-2 w-2 -translate-x-1/2 -translate-y-1/2 rotate-45 bg-black/90"></div>
            </div>
        </Teleport>
    </span>
</template>

<script setup>
import { computed, onBeforeUnmount, ref } from 'vue'

const props = defineProps({
    // Distinct values to list in the tooltip.
    values: {
        type: Array,
        default: () => [],
    },
    // Fallback trigger text if no slot content is provided.
    label: {
        type: String,
        default: '',
    },
    // Optional heading above the value list.
    heading: {
        type: String,
        default: '',
    },
})

const trigger = ref(null)
const visible = ref(false)
const coords = ref({ top: 0, left: 0 })

const tooltipStyle = computed(() => ({
    top: coords.value.top + 'px',
    left: coords.value.left + 'px',
    // Center horizontally and sit just above the trigger.
    transform: 'translate(-50%, calc(-100% - 10px))',
}))

const place = () => {
    const el = trigger.value
    if (!el) return
    const rect = el.getBoundingClientRect()
    coords.value = { top: rect.top, left: rect.left + rect.width / 2 }
}

const show = () => {
    place()
    visible.value = true
}

const hide = () => {
    visible.value = false
}

// Close a click-opened tooltip when clicking anywhere else (touch friendly).
const onDocumentClick = () => hide()

const toggle = () => {
    if (visible.value) {
        hide()
        document.removeEventListener('click', onDocumentClick)
        return
    }
    place()
    visible.value = true
    // Defer so this very click doesn't immediately close it.
    setTimeout(() => document.addEventListener('click', onDocumentClick, { once: true }), 0)
}

onBeforeUnmount(() => document.removeEventListener('click', onDocumentClick))
</script>
