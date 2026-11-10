<template>
    <section class="flex flex-col rounded-2xl border border-border-subtle bg-white p-5 shadow-sm">
        <header class="mb-4 flex items-center gap-x-3">
            <PropertyIcon v-if="icon" :name="icon" class="h-5 w-5 shrink-0 text-text-subtle" stroke-width="1.5" />
            <h3 class="font-lexend text-sm font-semibold text-text">{{ $t(title) }}</h3>
            <button
                v-if="canEdit"
                type="button"
                class="ml-auto rounded-full p-1.5 text-text-subtle transition hover:bg-surface-sunken hover:text-accent-700"
                :title="$t(actionLabel)"
                @click="$emit('action')"
            >
                <span class="sr-only">{{ $t(actionLabel) }}</span>
                <PropertyIcon :name="actionIcon" class="h-4 w-4" stroke-width="1.75" />
            </button>
        </header>
        <div class="flex-1 text-sm text-text">
            <slot />
        </div>
    </section>
</template>

<script setup>
import PropertyIcon from '@/Artwork/Icon/PropertyIcon.vue'

/**
 * Karte der Raum-Detailseite: Inhalt immer sichtbar, Bearbeiten über das Icon oben rechts.
 */
defineProps({
    title: { type: String, required: true },
    icon: { type: String, default: null },
    canEdit: { type: Boolean, default: false },
    actionIcon: { type: String, default: 'IconEdit' },
    actionLabel: { type: String, default: 'Edit' },
})

defineEmits(['action'])
</script>
