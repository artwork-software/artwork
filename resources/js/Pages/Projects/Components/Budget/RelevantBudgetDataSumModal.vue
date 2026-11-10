<template>
    <ArtworkBaseModal
        title="Details of the Selected Cell"
        description="Here you can view the relevant budget data for the selected cell. The displayed values come from the linked sub-projects and were determined based on the matching criteria of the group project."
        modal-size="sm:max-w-2xl"
        @close="emits('closed')"
    >
        <div class="overflow-hidden rounded-lg border border-border-subtle">
            <div class="hidden grid-cols-3 gap-4 bg-surface-sunken px-3 py-2 md:grid">
                <div class="text-xs font-medium text-text-muted">{{ $t('Project') }}</div>
                <div class="text-xs font-medium text-text-muted">{{ $t('Column') }}</div>
                <div class="text-right text-xs font-medium text-text-muted">{{ $t('Value') }}</div>
            </div>
            <ul class="divide-y divide-border-subtle">
                <li
                    v-for="(row, index) in data"
                    :key="`${row.subProjectName}-${row.relevantColumnName}-${index}`"
                    class="grid grid-cols-1 gap-1 px-3 py-2.5 md:grid-cols-3 md:gap-4"
                >
                    <div class="text-sm font-semibold text-text">{{ row.subProjectName }}</div>
                    <div class="text-sm text-text-muted">{{ row.relevantColumnName }}</div>
                    <div
                        class="text-sm font-semibold tabular-nums md:text-right"
                        :class="row.type === 'BUDGET_TYPE_COST' ? 'text-danger' : 'text-success'"
                    >
                        {{ row.type === 'BUDGET_TYPE_COST' ? '-' : '+' }}
                        {{ toCurrencyString(row.value) }} {{ $currencySymbol() }}
                    </div>
                </li>
            </ul>
        </div>
        <template #footer>
            <BaseUIButton
                label="Close"
                is-cancel-button
                hide-icon
                @click="emits('closed')"
            />
        </template>
    </ArtworkBaseModal>
</template>

<script setup>
import ArtworkBaseModal from "@/Artwork/Modals/ArtworkBaseModal.vue";
import BaseUIButton from "@/Artwork/Buttons/BaseUIButton.vue";
import {useInstanceFormat} from "@/Composeables/InstanceFormat.js";

defineOptions({name: 'RelevantBudgetDataSumModal'})

defineProps({
    data: {
        type: Array,
        default() {
            return []
        }
    }
})

// Die Elternkomponente (SubPositionComponent) hört auf "closed"
const emits = defineEmits(['closed'])

const {formatNumber} = useInstanceFormat()

// Betrag im Zahlenformat der Instanz (Einstellungen → Regionale Formate), Symbol steht daneben
const toCurrencyString = (value) => formatNumber(value ?? 0, 2)
</script>
