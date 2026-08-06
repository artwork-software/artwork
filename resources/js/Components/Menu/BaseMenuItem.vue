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
function wrapperClasses(disabled: boolean) {
    const base =
        'group inline-flex w-full min-h-8 items-center gap-2 rounded-md px-2.5 text-left text-[13px] transition-colors motion-reduce:transition-none select-none'

    if (disabled) {
        return [base, 'cursor-not-allowed text-text-subtle'].join(' ')
    }

    const tone = isDanger.value
        ? 'text-danger hover:bg-danger-surface'
        : 'text-text hover:bg-surface-sunken'
    return [base, '!cursor-pointer', tone].join(' ')
}

/** Icon-Farben passend zur Variante */
const iconClasses = computed(() => {
    const base = 'size-4 shrink-0 transition-colors motion-reduce:transition-none'
    if (isDisabled.value) {
        return `${base} text-text-subtle`
    }
    return isDanger.value
        ? `${base} text-danger`
        : `${base} text-text-muted group-hover:text-text`
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
