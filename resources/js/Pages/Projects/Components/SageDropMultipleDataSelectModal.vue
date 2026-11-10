<template>
    <ArtworkBaseModal
        title="Select Sage data"
        description="Which data should be moved?"
        modal-size="sm:max-w-3xl"
        @close="closeModal(false)"
    >
        <div class="overflow-x-auto rounded-md border border-border-subtle">
            <table class="w-full text-sm text-text">
                <thead class="bg-surface-sunken text-left text-xs font-semibold text-text-muted">
                    <tr>
                        <th scope="col" class="w-10 px-3 py-2">
                            <BaseCheckbox
                                id="sageDataSelectAll"
                                :model-value="selectAllState"
                                @update:model-value="toggleAll"
                            />
                        </th>
                        <th scope="col" class="px-3 py-2">{{ $t('KTO') }}</th>
                        <th scope="col" class="px-3 py-2">{{ $t('KST') }}</th>
                        <th scope="col" class="px-3 py-2">{{ $t('Booking text') }}</th>
                        <th scope="col" class="px-3 py-2 text-right">{{ $t('Booking amount') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-border-subtle">
                    <tr
                        v-for="data in sageAssignedData"
                        :key="data.id"
                        class="hover:bg-surface-hover"
                    >
                        <td class="px-3 py-2">
                            <BaseCheckbox
                                :id="`sageDataSelect-${data.id}`"
                                :model-value="selectedIds.has(data.id)"
                                @update:model-value="(checked) => toggleRow(data.id, checked)"
                            />
                        </td>
                        <td class="px-3 py-2 tabular-nums">{{ data.sa_kto }}</td>
                        <td class="px-3 py-2 tabular-nums">{{ data.kst_stelle }}</td>
                        <td class="max-w-64 truncate px-3 py-2" :title="data.buchungstext">{{ data.buchungstext }}</td>
                        <td class="px-3 py-2 text-right tabular-nums">{{ toCurrencyString(data.buchungsbetrag) }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
        <template #footer>
            <BaseUIButton
                label="No, not really"
                is-cancel-button
                hide-icon
                :disabled="processing"
                @click="closeModal(false)"
            />
            <BaseUIButton
                label="Move"
                variant="primary"
                icon="IconArrowsMove"
                :processing="processing"
                :disabled="selectedIds.size === 0"
                @click="moveRow"
            />
        </template>
    </ArtworkBaseModal>
</template>

<script setup>
import {computed, ref} from 'vue'
import {router, usePage} from '@inertiajs/vue3'
import ArtworkBaseModal from '@/Artwork/Modals/ArtworkBaseModal.vue'
import BaseUIButton from '@/Artwork/Buttons/BaseUIButton.vue'
import BaseCheckbox from '@/Artwork/Inputs/BaseCheckbox.vue'
import {createInstanceFormatter} from '@/Helper/instanceFormat.js'

defineOptions({name: 'SageDropMultipleDataSelectModal'})

const props = defineProps({
    cellData: {type: Object, required: true},
    cell: {type: Object, default: null},
    type: {type: String, required: true},
})

const emit = defineEmits(['close', 'budget-updated'])

const page = usePage()

// Auswahl lokal halten, statt checked in die Prop-Daten zu schreiben
const selectedIds = ref(new Set())
const processing = ref(false)

const sageAssignedData = computed(() => props.cellData?.sage_assigned_data ?? [])

const selectAllState = computed(() => {
    if (selectedIds.value.size === 0) {
        return false
    }
    return selectedIds.value.size === sageAssignedData.value.length ? true : 'indeterminate'
})

function toCurrencyString(value) {
    return createInstanceFormatter(page.props.instanceFormat).formatNumber(value ?? 0)
}

function toggleRow(id, checked) {
    const next = new Set(selectedIds.value)
    if (checked) {
        next.add(id)
    } else {
        next.delete(id)
    }
    selectedIds.value = next
}

function toggleAll(checked) {
    selectedIds.value = checked
        ? new Set(sageAssignedData.value.map((data) => data.id))
        : new Set()
}

function closeModal(bool) {
    emit('close', bool)
}

function moveUrl() {
    if (props.type === 'dropOnValue') {
        return route('project.budget.move.sage.row', {
            columnCell: props.cell.id,
            movedColumn: props.cellData.id,
        })
    }
    if (props.type === 'dropOnRow') {
        return route('project.budget.move.sage.to.row', {
            table_id: props.cellData.table_id,
            sub_position_id: props.cellData.sub_position_id,
            positionBefore: props.cellData.positionBefore,
            columnCell: props.cellData.id,
        })
    }
    return null
}

function moveRow() {
    const url = moveUrl()
    if (!url || selectedIds.value.size === 0 || processing.value) {
        return
    }
    processing.value = true
    router.post(url, {
        multiple: true,
        selectedData: sageAssignedData.value
            .filter((data) => selectedIds.value.has(data.id))
            .map((data) => data.id),
    }, {
        preserveState: true,
        preserveScroll: true,
        onSuccess: () => {
            emit('budget-updated')
            closeModal(false)
        },
        onFinish: () => {
            processing.value = false
        },
    })
}
</script>
