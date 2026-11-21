<template>
    <TrashTab
        :paginator="sageNotAssignedDataTrashed"
        property-name="sageNotAssignedDataTrashed"
        :force-all-href="route('sageNotAssignedData.forceDeleteAll')"
        :restore-href="(entry) => route('sageNotAssignedData.restore', { sageNotAssignedData: entry.id })"
        :force-href="(entry) => route('sageNotAssignedData.forceDelete', { sageNotAssignedData: entry.id })"
        :name-of="(entry) => String(entry.buchungstext ?? '')"
    >
        <template #default="{ item: entry }">
            <div class="flex flex-wrap items-baseline gap-x-3">
                <span class="truncate font-medium text-text">{{ entry.buchungstext || $t('Without booking text') }}</span>
                <span class="text-sm text-text tabular-nums">{{ entry.buchungsbetrag }} EUR</span>
            </div>
            <div class="mt-0.5 truncate text-sm text-text-muted">
                <span v-if="entry.kreditor">{{ $t('Creditor') }}: {{ entry.kreditor }}</span>
                <template v-if="entry.belegnummer">
                    <span class="text-text-subtle"> · </span>
                    <span>{{ $t('Document number') }}: {{ entry.belegnummer }}</span>
                </template>
                <template v-if="entry.belegdatum">
                    <span class="text-text-subtle"> · </span>
                    <span>{{ $t('Document date') }}: {{ formatDate(entry.belegdatum) }}</span>
                </template>
            </div>
            <button type="button" class="mt-0.5 text-xs text-accent-600 hover:text-accent-700"
                    :aria-expanded="openedIds.includes(entry.id)"
                    @click="toggleDetails(entry.id)">
                {{ openedIds.includes(entry.id) ? $t('Hide details') : $t('Show details') }}
            </button>
            <dl v-if="openedIds.includes(entry.id)" class="mt-2 grid max-w-xl grid-cols-2 gap-x-4 gap-y-1 text-sm">
                <dt class="text-text-subtle">{{ $t('General ledger account') }}</dt>
                <dd class="text-text">{{ entry.sa_kto }}</dd>
                <dt class="text-text-subtle">{{ $t('Cost bearer') }}</dt>
                <dd class="text-text">{{ entry.kst_traeger }}</dd>
                <dt class="text-text-subtle">{{ $t('Cost center') }}</dt>
                <dd class="text-text">{{ entry.kst_stelle }}</dd>
                <dt class="text-text-subtle">{{ $t('Booking date') }}</dt>
                <dd class="text-text">{{ formatDate(entry.buchungsdatum) }}</dd>
            </dl>
        </template>
    </TrashTab>
</template>

<script>
import AppLayout from '@/Layouts/AppLayout.vue'
import TrashLayout from '@/Layouts/TrashLayout.vue'

export default {
    layout: [AppLayout, TrashLayout],
}
</script>

<script setup>
import TrashTab from '@/Pages/Trash/Components/TrashTab.vue'
import { ref } from 'vue'

defineProps({
    sageNotAssignedDataTrashed: { type: Object, required: true },
})

const openedIds = ref([])

const toggleDetails = (id) => {
    openedIds.value = openedIds.value.includes(id)
        ? openedIds.value.filter((openedId) => openedId !== id)
        : [...openedIds.value, id]
}

// "2026-11-20T00:00:00" → "20.11.2026"; leere Werte bleiben leer
const formatDate = (value) => {
    const [year, month, day] = String(value ?? '').split('T')[0].split('-')

    return day ? `${day}.${month}.${year}` : ''
}
</script>
