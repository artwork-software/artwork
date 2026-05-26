<template>
    <div class="mb-8 border-b border-dashed border-gray-400 pb-6">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-sm font-semibold text-gray-900">{{ $t('Snapshots') }}</h3>
        </div>

        <div v-if="canEdit" class="flex items-end gap-3 mb-4 max-w-xl">
            <BaseInput
                id="snapshot_name"
                v-model="newName"
                :label="$t('Name')"
                class="flex-1"
            />
            <BaseInput
                type="date"
                id="snapshot_date"
                v-model="newDate"
                :label="$t('Date')"
                class="w-44"
            />
            <BaseUIButton @click="createSnapshot" :label="$t('Create')" is-add-button :disabled="!newName || !newDate" />
        </div>

        <div v-if="snapshots.length > 0" class="mb-5 rounded-lg border border-gray-200 p-3 bg-gray-50/60">
            <div class="flex items-end gap-3 max-w-md">
                <ArtworkBaseListbox
                    class="flex-1"
                    :model-value="compareSnapshot"
                    @update:model-value="value => compareId = value?.id ?? null"
                    :items="snapshots"
                    by="id"
                    :option-label="snapshotLabel"
                    :label="$t('Compare snapshot with current values')"
                    :placeholder="$t('Select snapshot')"
                />
                <button v-if="compareId" @click="compareId = null" class="text-xs text-gray-500 hover:text-gray-700 pb-2">
                    {{ $t('Reset') }}
                </button>
            </div>

            <table v-if="comparison.length > 0" class="min-w-full text-xs mt-4">
                <thead>
                    <tr class="text-left text-gray-500 border-b border-gray-200">
                        <th class="px-2 py-1">{{ $t('Metric') }}</th>
                        <th class="px-2 py-1 text-right">{{ $t('Snapshot') }}</th>
                        <th class="px-2 py-1 text-right">{{ $t('Current') }}</th>
                        <th class="px-2 py-1 text-right">{{ $t('Difference') }}</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="metric in comparison" :key="metric.label" class="border-b border-gray-100">
                        <td class="px-2 py-1 text-gray-700">{{ $t(metric.label) }}</td>
                        <td class="px-2 py-1 text-right text-gray-500">{{ formatNumber(metric.snapshotValue) }}</td>
                        <td class="px-2 py-1 text-right text-gray-900">{{ formatNumber(metric.currentValue) }}</td>
                        <td :class="['px-2 py-1 text-right font-medium', metric.delta > 0 ? 'text-emerald-600' : (metric.delta < 0 ? 'text-rose-600' : 'text-gray-400')]">
                            {{ metric.delta > 0 ? '+' : '' }}{{ formatNumber(metric.delta) }}
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="space-y-3" v-if="snapshots.length > 0">
            <div
                v-for="snapshot in snapshots"
                :key="snapshot.id"
                class="rounded-lg border border-gray-200 p-3"
            >
                <div class="flex items-center justify-between">
                    <div>
                        <span class="font-medium text-sm text-gray-900">{{ snapshot.name }}</span>
                        <span class="text-xs text-gray-400 ml-2">{{ formatDate(snapshot.snapshot_date) }}</span>
                        <span class="text-xs text-gray-400 ml-2">{{ $t('by') }} {{ snapshot.creator?.first_name }} {{ snapshot.creator?.last_name }}</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <button @click="toggleDetail(snapshot.id)" class="text-xs text-primary hover:underline">
                            {{ expandedId === snapshot.id ? $t('Hide') : $t('Show') }}
                        </button>
                        <button v-if="canEdit" @click="deleteSnapshot(snapshot.id)" class="text-xs text-red-500 hover:text-red-700 print:hidden">
                            {{ $t('Delete') }}
                        </button>
                    </div>
                </div>
                <div v-if="expandedId === snapshot.id" class="mt-3 text-xs text-gray-600 bg-gray-50 rounded p-3 overflow-auto max-h-64">
                    <pre class="whitespace-pre-wrap">{{ JSON.stringify(snapshot.data, null, 2) }}</pre>
                </div>
            </div>
        </div>
        <p v-else class="text-sm text-gray-400">{{ $t('No snapshots created yet.') }}</p>
    </div>
</template>

<script setup>
import { ref, computed } from 'vue';
import BaseInput from '@/Artwork/Inputs/BaseInput.vue';
import BaseUIButton from "@/Artwork/Buttons/BaseUIButton.vue";
import ArtworkBaseListbox from '@/Artwork/Listbox/ArtworkBaseListbox.vue';

const props = defineProps({
    snapshots: { type: Array, default: () => [] },
    canEdit: { type: Boolean, default: false },
    projectId: { type: Number, required: true },
    current: { type: Object, default: () => ({}) },
});

const emit = defineEmits(['updated']);

const newName = ref('');
const newDate = ref('');
const expandedId = ref(null);
const compareId = ref(null);

const formatNumber = (value) => {
    const n = Number(value ?? 0);
    if (Number.isNaN(n)) return '0';
    return new Intl.NumberFormat('de-DE', { maximumFractionDigits: 2 }).format(n);
};

const snapshotLabel = (snapshot) => `${snapshot.name} (${formatDate(snapshot.snapshot_date)})`;

const compareSnapshot = computed(() => props.snapshots.find(s => s.id === compareId.value) ?? null);

const flatten = (data) => {
    const num = (v) => {
        const n = Number(v ?? 0);
        return Number.isNaN(n) ? 0 : n;
    };
    const bd = data?.bi_data ?? {};
    const dv = data?.derived_values ?? {};
    const map = {
        'Total visitors': num(bd.visitors_total),
        'Total sold tickets': num(bd.sold_tickets_total),
        'Revenue': num(bd.revenue_total),
        'Contracts': num(dv.contract_count),
        'Events': num(dv.event_count),
        'Bookings': num(dv.booking_count),
        'Tasks total': num(dv.task_total),
        'Tasks open': num(dv.task_open),
        'Documents': num(dv.document_count),
    };
    (data?.tag_counts ?? []).forEach((t) => {
        map[t.tag_name_de || t.tag_name] = num(t.count);
    });
    return map;
};

const comparison = computed(() => {
    if (!compareSnapshot.value) return [];
    const snap = flatten(compareSnapshot.value.data);
    const cur = flatten(props.current);
    const labels = [...new Set([...Object.keys(snap), ...Object.keys(cur)])];
    return labels.map((label) => {
        const snapshotValue = snap[label] ?? 0;
        const currentValue = cur[label] ?? 0;
        return { label, snapshotValue, currentValue, delta: currentValue - snapshotValue };
    });
});

const formatDate = (value) => {
    if (!value) return '-';
    const date = new Date(value);
    if (isNaN(date.getTime())) return value;
    const day = String(date.getDate()).padStart(2, '0');
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const year = date.getFullYear();
    return `${day}.${month}.${year}`;
};

const toggleDetail = (id) => {
    expandedId.value = expandedId.value === id ? null : id;
};

const createSnapshot = async () => {
    if (!newName.value || !newDate.value) return;
    try {
        await axios.post(route('projects.bi.snapshots.store', props.projectId), {
            name: newName.value,
            snapshot_date: newDate.value,
        });
        newName.value = '';
        newDate.value = '';
        emit('updated');
    } catch (error) {
        console.error('Error creating snapshot', error);
    }
};

const deleteSnapshot = async (snapshotId) => {
    if (!confirm('Delete this snapshot? This cannot be undone.')) return;
    try {
        await axios.delete(route('projects.bi.snapshots.destroy', [props.projectId, snapshotId]));
        emit('updated');
    } catch (error) {
        console.error('Error deleting snapshot', error);
    }
};
</script>
