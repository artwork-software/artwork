<template>
    <div class="flex flex-col items-center justify-center rounded-lg border-2 border-dashed border-border px-6 py-10 text-center">
        <PropertyIcon
            v-if="icon"
            :name="icon"
            class="mb-3 size-6 text-text-subtle"
            :stroke-width="1.5"
            aria-hidden="true"
        />
        <h3 class="font-lexend text-[15px] font-semibold text-text">
            {{ title }}
        </h3>
        <p v-if="description" class="mt-1 max-w-md text-xs text-text-muted">
            {{ description }}
        </p>
        <div v-if="actionLabel" class="mt-4">
            <BaseUIButton
                variant="secondary"
                size="sm"
                :label="actionLabel"
                hide-icon
                @click="$emit('action')"
            />
        </div>
    </div>
</template>

<script setup>
import PropertyIcon from '@/Artwork/Icon/PropertyIcon.vue'
import BaseUIButton from '@/Artwork/Buttons/BaseUIButton.vue'

defineOptions({ name: 'EmptyState' })

defineProps({
    /** Überschrift des Leerzustands */
    title: {
        type: String,
        required: true,
    },
    /** Optionale Beschreibung unter der Überschrift */
    description: {
        type: String,
        default: '',
    },
    /** Optionales Label für die Aktion; ohne Label wird kein Button gerendert */
    actionLabel: {
        type: String,
        default: '',
    },
    /** Optionales Tabler-Icon oberhalb des Titels */
    icon: {
        type: String,
        default: '',
    },
})

defineEmits(['action'])
</script>
