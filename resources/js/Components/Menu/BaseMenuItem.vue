<template>
    <MenuItem
        :as="asLink ? Link : 'button'"
        :href="asLink ? link : undefined"
        :disabled="isDisabled"
        :class="wrapperClasses(isDisabled)"
        @click="handleClick"
    >
        <PropertyIcon
            :name="icon"
            aria-hidden="true"
            :class="iconClasses"
        />
        <span class="truncate">
          {{ withoutTranslation ? title : $t(title) }}
        </span>
    </MenuItem>
</template>

<script setup lang="ts">
import type { Component, PropType } from 'vue'
import { computed } from 'vue'
import { MenuItem } from '@headlessui/vue'
import { Link } from '@inertiajs/vue3'
import { IconTrash } from '@tabler/icons-vue'
import PropertyIcon from '@/Artwork/Icon/PropertyIcon.vue'

const props = defineProps({
    asLink: { type: Boolean, default: false },
    link: { type: String, default: '' },
    icon: { type: [String, Function, Object] as PropType<string | Component>, default: null },
    title: { type: String, default: '' },
    withoutTranslation: { type: Boolean, default: false },
    whiteMenuBackground: { type: Boolean, default: false }, // true = helles Menü
    disabled: { type: Boolean, default: false },
    danger: { type: Boolean, default: false },               // destructive Style erzwingen
})

const emit = defineEmits<{ (e:'click', ev: MouseEvent): void }>()

const isDisabled = computed(() => !!props.disabled)

/** Destructive erkennen (Prop > Icon > Titel) */
const isDanger = computed(() => {
    const name = (props.title || '').toLowerCase()
    const looksLikeTrash =
        props.icon === IconTrash || props.icon === 'IconTrash' || /delete|löschen|remove/.test(name)
    return props.danger || looksLikeTrash
})

/** Wrapper-Klassen (Headless UI setzt active/disabled; wir stylen darauf) */
function wrapperClasses(active: boolean, disabled: boolean) {
    const base =
        'group inline-flex w-full items-center gap-2 rounded-lg px-3 py-2 text-xs transition-colors select-none'
    const state = disabled ? 'opacity-50 cursor-not-allowed' : '!cursor-pointer'

    if (props.whiteMenuBackground) {
        // helles Menü
        const tone = isDanger.value
            ? 'text-red-600 hover:bg-red-50 hover:text-red-700'
            : 'text-gray-700 hover:bg-gray-50 hover:text-blue-600'
        return [base, state, tone].join(' ')
    } else {
        // dunkles Menü
        const tone = isDanger.value
            ? 'text-red-300 hover:bg-red-500/10 hover:text-red-200'
            : 'text-white hover:bg-white/10'
        const activeBg = active ? 'bg-white/10' : ''
        return [base, state, tone].join(' ')
    }
}

/** Icon-Farben passend zu Variant/Hintergrund */
const iconClasses = computed(() => {
    const base = 'mr-2 size-4 shrink-0 transition-colors'
    if (props.whiteMenuBackground) {
        return isDanger.value
            ? `${base} text-red-500 group-hover:text-red-600`
            : `${base} text-gray-400 group-hover:text-blue-600`
    } else {
        return isDanger.value
            ? `${base} text-red-300 group-hover:text-red-200`
            : `${base} text-white/80 group-hover:text-white`
    }
})

function handleClick(ev: MouseEvent) {
    if (isDisabled.value) {
        ev.preventDefault()
        ev.stopPropagation()
        return
    }
    // Bei Link übernimmt Navigation Inertia automatisch.
    // Bei Button-Variante event nach außen reichen:
    if (!props.asLink) emit('click', ev)
}
</script>
