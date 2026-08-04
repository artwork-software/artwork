<template>
    <component
        :is="href ? Link : 'div'"
        :href="href || undefined"
        class="block rounded-lg border border-border-subtle bg-surface p-4"
        :class="href ? 'transition-colors duration-150 ease-out hover:border-border focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-accent-600' : ''"
    >
        <div class="flex items-start justify-between gap-2">
            <span class="text-xs text-text-muted">{{ label }}</span>
            <PropertyIcon
                v-if="icon"
                :name="icon"
                class="size-[18px] shrink-0 text-text-subtle"
                :stroke-width="1.5"
                aria-hidden="true"
            />
        </div>
        <div class="mt-1 font-lexend text-2xl font-semibold text-text tabular-nums">
            {{ value }}
        </div>
        <div v-if="trend" class="mt-1 text-[11px] font-semibold text-text-muted tabular-nums">
            {{ trend }}
        </div>
    </component>
</template>

<script setup>
import { Link } from '@inertiajs/vue3'
import PropertyIcon from '@/Artwork/Icon/PropertyIcon.vue'

defineOptions({ name: 'KpiTile' })

defineProps({
    /** Beschriftung der Kennzahl */
    label: {
        type: String,
        required: true,
    },
    /** Wert der Kennzahl */
    value: {
        type: [String, Number],
        required: true,
    },
    /** Optionales Tabler-Icon rechts oben */
    icon: {
        type: String,
        default: '',
    },
    /** Optionale Trend-/Zusatzzeile unter dem Wert */
    trend: {
        type: String,
        default: '',
    },
    /** Optionales Ziel — macht die Kachel zu einem Inertia-Link */
    href: {
        type: String,
        default: '',
    },
})
</script>
