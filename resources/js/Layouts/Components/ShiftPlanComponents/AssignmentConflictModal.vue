<template>
    <!-- Warnung vor der Zuweisung (Vorabprüfung): Überschneidung, Urlaub, nicht verfügbar.
         Die Zuweisung bleibt möglich — der Konflikt wird danach an der Schicht markiert. -->
    <ArtworkBaseModal
        title="Conflicts when assigning"
        description="Please check the conflicts before assigning."
        modal-size="sm:max-w-lg"
        is-in-shift-plan
        @close="$emit('cancel')"
    >
        <div class="space-y-4">
            <!-- Person → Schicht (Datum, Zeit, Gewerk); dynamischer Text bewusst nicht durch $t -->
            <div v-if="contextLine" class="text-sm text-text tabular-nums">{{ contextLine }}</div>

            <ul class="divide-y divide-border-subtle rounded-lg border border-border-subtle">
                <li
                    v-for="(conflict, index) in conflicts"
                    :key="index"
                    class="flex items-start gap-x-3 px-3 py-2.5"
                >
                    <span
                        class="mt-0.5 inline-flex size-6 shrink-0 items-center justify-center rounded-full"
                        :class="iconWrapperClass(conflict.type)"
                    >
                        <PropertyIcon :name="iconFor(conflict.type)" class="size-4" stroke-width="1.5" aria-hidden="true" />
                    </span>
                    <div class="min-w-0">
                        <div class="text-sm font-medium text-text">{{ conflict.label }}</div>
                        <div v-if="conflict.detail" class="text-xs text-text-muted tabular-nums">{{ conflict.detail }}</div>
                    </div>
                </li>
            </ul>

            <div class="flex items-start gap-2 rounded-lg border border-warning-border bg-warning-surface px-3 py-2 text-xs text-warning">
                <PropertyIcon name="IconInfoCircle" class="mt-px size-4 shrink-0" stroke-width="1.5" aria-hidden="true" />
                <span>{{ $t('You can still assign; the conflict will be marked on the shift.') }}</span>
            </div>
        </div>

        <template #footer>
            <BaseUIButton type="button" :label="$t('Cancel')" is-cancel-button @click="$emit('cancel')" />
            <BaseUIButton type="button" :label="$t('Assign anyway')" is-add-button @click="$emit('confirm')" />
        </template>
    </ArtworkBaseModal>
</template>

<script setup>
import { computed } from 'vue'
import ArtworkBaseModal from '@/Artwork/Modals/ArtworkBaseModal.vue'
import BaseUIButton from '@/Artwork/Buttons/BaseUIButton.vue'
import PropertyIcon from '@/Artwork/Icon/PropertyIcon.vue'

defineOptions({ name: 'AssignmentConflictModal' })

const props = defineProps({
    /** [{ type: 'overlap'|'vacation'|'unavailable', label, detail }] */
    conflicts: { type: Array, default: () => [] },
    personName: { type: String, default: '' },
    /** { date, start, end, craft } */
    shift: { type: Object, default: () => ({}) },
})

defineEmits(['cancel', 'confirm'])

// Kontextzeile: „Anna Muster → 07.09.2026, 10:00–18:00, Technik"
const contextLine = computed(() => {
    const parts = [props.shift?.date, [props.shift?.start, props.shift?.end].filter(Boolean).join('–'), props.shift?.craft]
        .filter(Boolean)
    const shiftText = parts.join(', ')
    if (props.personName && shiftText) return `${props.personName} → ${shiftText}`
    return props.personName || shiftText || ''
})

function iconFor(type) {
    switch (type) {
        case 'overlap':
            return 'IconClockExclamation'
        case 'vacation':
            return 'IconBeach'
        case 'unavailable':
            return 'IconUserOff'
        default:
            return 'IconAlertTriangle'
    }
}

function iconWrapperClass(type) {
    switch (type) {
        case 'overlap':
            return 'bg-danger-surface text-danger'
        case 'vacation':
            return 'bg-accent-50 text-accent-700'
        default:
            return 'bg-warning-surface text-warning'
    }
}
</script>
