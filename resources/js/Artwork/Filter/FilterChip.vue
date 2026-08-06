<template>
    <span class="inline-flex h-[22px] items-center gap-1 rounded-full border border-accent-200 bg-accent-50 pl-2 pr-1 text-[11.5px] font-medium text-accent-700">
        <span class="truncate">{{ label }}</span>
        <button
            type="button"
            class="inline-flex size-4 shrink-0 items-center justify-center rounded-full text-accent-700 transition-colors duration-150 ease-out hover:bg-accent-100 focus-visible:outline-2 focus-visible:outline-offset-1 focus-visible:outline-accent-600"
            :aria-label="t(removeLabel)"
            @click="$emit('remove')"
        >
            <PropertyIcon name="IconX" class="size-3" :stroke-width="1.5" aria-hidden="true" />
        </button>
    </span>
</template>

<script setup>
import { useI18n } from 'vue-i18n'
import PropertyIcon from '@/Artwork/Icon/PropertyIcon.vue'

defineOptions({ name: 'FilterChip' })

const { t } = useI18n()

defineProps({
    /** Angezeigter Filtertext */
    label: {
        type: String,
        required: true,
    },
    /** i18n-Key für das aria-label des Entfernen-Buttons ('Remove' → 'Entfernen' in de.json) */
    removeLabel: {
        type: String,
        default: 'Remove',
    },
})

defineEmits(['remove'])
</script>
