<template>
    <!-- Schmale Hinweisleiste unter der Funktionsleiste, solange der Personenfilter
         „nur Personen mit offenen Regelverstößen" aktiv ist. Liegt bewusst außerhalb des
         Scroll-/Grid-Containers, damit sie nicht mit dem Raster neu gerendert wird. -->
    <div
        class="flex flex-wrap items-center gap-x-3 gap-y-1.5 rounded-md border border-warning-border bg-warning-surface px-4 py-2 text-sm text-warning"
        role="status"
    >
        <IconFilterFilled class="size-4 shrink-0" stroke-width="1.5" aria-hidden="true" />
        <span>{{ $t('Filter active: only people with open rule violations are shown.') }}</span>
        <span v-if="isEmpty" class="font-medium">
            {{ $t('No people with open rule violations in this period.') }}
        </span>
        <button
            type="button"
            class="ui-button-small ml-auto shrink-0 whitespace-nowrap"
            :title="$t('Remove filter: only people with open rule violations')"
            @click="emit('remove')"
        >
            <IconX class="size-3.5" stroke-width="2" aria-hidden="true" />
            {{ $t('Remove filter') }}
        </button>
    </div>
</template>

<script setup lang="ts">
import { IconFilterFilled, IconX } from '@tabler/icons-vue'

defineOptions({ name: 'ShiftPlanOpenViolationsFilterNotice' })

defineProps<{
    /** Gefiltertes Ergebnis ist leer (keine Person/Schicht mit offenem Verstoß im Zeitraum) */
    isEmpty?: boolean
}>()

const emit = defineEmits<{
    (e: 'remove'): void
}>()
</script>
