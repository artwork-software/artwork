<template>
    <div>
        <div v-if="canEdit" class="mb-4 grid grid-cols-1 gap-3 sm:grid-cols-[minmax(0,1fr)_10rem_9rem_auto] sm:items-end max-w-3xl print:hidden">
            <BaseInput
                id="snapshot_name"
                v-model="newName"
                :label="$t('Name')"
            />
            <BaseInput
                type="date"
                id="snapshot_date"
                v-model="newDate"
                :label="$t('Date')"
            />
            <ArtworkBaseListbox
                :model-value="newScopeItem"
                @update:model-value="value => newScope = value?.id ?? 'actual'"
                :items="scopeItems"
                by="id"
                option-label="name"
                :label="$t('Data set')"
            />
            <BaseUIButton @click="createSnapshot" :label="$t('Create')" is-add-button :disabled="!newName || !newDate" />
        </div>

        <!-- Entwicklung über alle Snapshots -->
        <div v-if="trendChart" class="mb-5 rounded-lg border border-border-subtle p-4 print:hidden">
            <h4 class="text-xs font-medium text-text-subtle mb-2">{{ $t('Trend across snapshots') }}</h4>
            <BiChart type="line" :data="trendChart" :options="trendOptions" height="220px" />
        </div>

        <!-- Vergleich Snapshot ↔ aktuell -->
        <div v-if="snapshots.length > 0" class="mb-5 rounded-lg border border-border-subtle p-3 bg-surface-sunken/60">
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
                <button v-if="compareId" @click="compareId = null" class="text-xs text-text-subtle hover:text-text-muted pb-2">
                    {{ $t('Reset') }}
                </button>
            </div>

            <table v-if="comparison.length > 0" class="min-w-full text-sm mt-4">
                <thead>
                    <tr class="text-left text-xs text-text-subtle border-b border-border-subtle">
                        <th class="px-2 py-1.5">{{ $t('Metric') }}</th>
                        <th class="px-2 py-1.5 text-right">{{ $t('Snapshot') }}</th>
                        <th class="px-2 py-1.5 text-right">{{ $t('Current') }}</th>
                        <th class="px-2 py-1.5 text-right">{{ $t('Difference') }}</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="metric in comparison" :key="metric.label" class="border-b border-border-subtle">
                        <td class="px-2 py-1.5 text-text-muted">{{ metric.translate ? $t(metric.label) : metric.label }}</td>
                        <td class="px-2 py-1.5 text-right text-text-subtle">{{ formatNumber(metric.snapshotValue) }}</td>
                        <td class="px-2 py-1.5 text-right text-text font-medium">{{ formatNumber(metric.currentValue) }}</td>
                        <td class="px-2 py-1.5 text-right">
                            <span
                                class="inline-flex items-center gap-0.5 rounded-full px-2 py-0.5 text-xs font-medium"
                                :class="metric.delta > 0
                                    ? 'bg-success-surface text-success'
                                    : (metric.delta < 0 ? 'bg-danger-surface text-danger' : 'bg-surface-sunken text-text-subtle')"
                            >
                                <template v-if="metric.delta > 0">▲</template>
                                <template v-else-if="metric.delta < 0">▼</template>
                                {{ metric.delta > 0 ? '+' : '' }}{{ formatNumber(metric.delta) }}
                            </span>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Snapshot-Liste -->
        <div class="space-y-3" v-if="snapshots.length > 0">
            <div
                v-for="snapshot in snapshots"
                :key="snapshot.id"
                class="rounded-lg border border-border-subtle p-3"
            >
                <div class="flex items-center justify-between">
                    <div>
                        <span class="font-medium text-sm text-text">{{ snapshot.name }}</span>
                        <span
                            v-if="snapshot.scope === 'plan'"
                            class="ml-2 rounded-full bg-accent-100 px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide text-accent-700"
                        >
                            {{ $t('Plan') }}
                        </span>
                        <span class="text-xs text-text-subtle ml-2">{{ formatDate(snapshot.snapshot_date) }}</span>
                        <span class="text-xs text-text-subtle ml-2">{{ $t('by') }} {{ snapshot.creator?.first_name }} {{ snapshot.creator?.last_name }}</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <button @click="toggleDetail(snapshot.id)" class="text-xs text-text hover:underline print:hidden">
                            {{ expandedId === snapshot.id ? $t('Hide') : $t('Show') }}
                        </button>
                        <button v-if="canEdit" @click="snapshotToDelete = snapshot" class="text-xs text-danger hover:text-danger print:hidden">
                            {{ $t('Delete') }}
                        </button>
                    </div>
                </div>
                <div v-if="expandedId === snapshot.id" class="mt-3 grid grid-cols-2 md:grid-cols-4 gap-2">
                    <div
                        v-for="metric in flattenList(snapshot.data)"
                        :key="metric.label"
                        class="rounded-md bg-surface-sunken px-2.5 py-2"
                    >
                        <span class="block text-[11px] text-text-subtle truncate">{{ metric.translate ? $t(metric.label) : metric.label }}</span>
                        <span class="text-sm font-semibold text-text">{{ formatNumber(metric.value) }}</span>
                    </div>
                </div>
            </div>
        </div>
        <p v-else class="text-sm text-text-subtle">{{ $t('No snapshots created yet.') }}</p>

        <ArtworkBaseDeleteModal
            v-if="snapshotToDelete"
            :title="$t('Delete snapshot?')"
            :description="`${snapshotToDelete.name} (${formatDate(snapshotToDelete.snapshot_date)}) — ${$t('This snapshot will be removed permanently.')}`"
            @close="snapshotToDelete = null"
            @delete="deleteSnapshot"
        />
    </div>
</template>

<script setup>
import { ref, computed } from 'vue';
import { usePage } from '@inertiajs/vue3';
import BaseInput from '@/Artwork/Inputs/BaseInput.vue';
import BaseUIButton from "@/Artwork/Buttons/BaseUIButton.vue";
import ArtworkBaseListbox from '@/Artwork/Listbox/ArtworkBaseListbox.vue';
import ArtworkBaseDeleteModal from '@/Artwork/Modals/ArtworkBaseDeleteModal.vue';
import BiChart from '@/Artwork/Charts/BiChart.vue';
import { useTranslation } from '@/Composeables/Translation.js';
import { useBiSaveFeedback } from '@/Composeables/BiSaveFeedback.js';

const t = useTranslation();

// Sage-Buchungen nur mit aktiver Schnittstelle in den Kennzahl-Grids zeigen
const sageApiEnabled = !!usePage().props.sageApiEnabled;

const props = defineProps({
    snapshots: { type: Array, default: () => [] },
    canEdit: { type: Boolean, default: false },
    projectId: { type: Number, required: true },
    current: { type: Object, default: () => ({}) },
    audienceCategories: { type: Array, default: () => [] },
});

const emit = defineEmits(['updated']);

const categoryNameById = computed(() => new Map(
    props.audienceCategories.map(category => [category.id, category.name])
));

const newName = ref('');
// Stichtag ist fast immer "heute" → vorbelegen
const newDate = ref(new Date().toISOString().slice(0, 10));
const expandedId = ref(null);
const snapshotToDelete = ref(null);
const compareId = ref(null);

// Scope-Wahl beim Anlegen: Ist- oder Plan-Datensatz einfrieren
const scopeItems = [
    { id: 'actual', name: t('Actual') },
    { id: 'plan', name: t('Plan') },
];
const newScope = ref('actual');
const newScopeItem = computed(() => scopeItems.find(i => i.id === newScope.value) ?? scopeItems[0]);

const currencyFmt = new Intl.NumberFormat('de-DE', { style: 'currency', currency: 'EUR' });

const formatNumber = (value) => {
    const n = Number(value ?? 0);
    if (Number.isNaN(n)) return '0';
    return new Intl.NumberFormat('de-DE', { maximumFractionDigits: 2 }).format(n);
};

const snapshotLabel = (snapshot) => `${snapshot.name} (${formatDate(snapshot.snapshot_date)})`;

const compareSnapshot = computed(() => props.snapshots.find(s => s.id === compareId.value) ?? null);

const num = (v) => {
    const n = Number(v ?? 0);
    return Number.isNaN(n) ? 0 : n;
};

// Kennzahl aus Gesamtwert ODER Summe der Pro-Termin-Werte — je nach Erfassungsmodus des Snapshots
const metricFromSnapshot = (data, totalField, eventField) => {
    const total = data?.bi_data?.[totalField];
    if (total !== null && total !== undefined) return num(total);
    return (data?.event_data ?? []).reduce((sum, entry) => sum + num(entry?.[eventField]), 0);
};

const flatten = (data) => {
    const dv = data?.derived_values ?? {};
    const map = new Map([
        ['Total visitors', { value: metricFromSnapshot(data, 'visitors_total', 'visitors'), translate: true }],
        ['Total sold tickets', { value: metricFromSnapshot(data, 'sold_tickets_total', 'sold_tickets'), translate: true }],
        ['Revenue', { value: metricFromSnapshot(data, 'revenue_total', 'revenue'), translate: true }],
        ['Contracts', { value: num(dv.contract_count), translate: true }],
        ['Events', { value: num(dv.event_count), translate: true }],
        ['Tasks total', { value: num(dv.task_total), translate: true }],
        ['Tasks open', { value: num(dv.task_open), translate: true }],
        ['Documents', { value: num(dv.document_count), translate: true }],
    ]);
    if (sageApiEnabled) {
        map.set('Bookings', { value: num(dv.booking_count), translate: true });
    }
    (data?.tag_counts ?? []).forEach((tag) => {
        map.set(tag.tag_name_de || tag.tag_name, { value: num(tag.count), translate: false });
    });
    // Besucher*innen-Kategorien: Summe je Kategorie (Gesamt- oder Terminwerte)
    const categorySums = new Map();
    (data?.category_values ?? []).forEach((entry) => {
        if (entry?.quantity === null || entry?.quantity === undefined) return;
        const id = entry.bi_audience_category_id;
        categorySums.set(id, (categorySums.get(id) ?? 0) + num(entry.quantity));
    });
    categorySums.forEach((value, id) => {
        const name = categoryNameById.value.get(id) ?? `${t('Category')} #${id}`;
        map.set(name, { value, translate: false });
    });
    return map;
};

const flattenList = (data) => [...flatten(data).entries()].map(
    ([label, entry]) => ({ label, value: entry.value, translate: entry.translate })
);

const comparison = computed(() => {
    if (!compareSnapshot.value) return [];
    const snap = flatten(compareSnapshot.value.data);
    const cur = flatten(props.current);
    const labels = [...new Set([...snap.keys(), ...cur.keys()])];
    return labels.map((label) => {
        const snapshotValue = snap.get(label)?.value ?? 0;
        const currentValue = cur.get(label)?.value ?? 0;
        return {
            label,
            translate: snap.get(label)?.translate ?? cur.get(label)?.translate ?? true,
            snapshotValue,
            currentValue,
            delta: currentValue - snapshotValue,
        };
    });
});

// Verlauf über alle Snapshots (chronologisch) + aktueller Stand als letzter Punkt
const trendChart = computed(() => {
    // Verlaufslinie nur über Ist-Snapshots — Plan-Snapshots sind Zielmarken,
    // keine Zeitreihe (sie erscheinen als gestrichelte Referenzlinien).
    const actualSnapshots = props.snapshots.filter(s => (s.scope ?? 'actual') === 'actual');
    if (actualSnapshots.length < 2) return null;

    const sorted = [...actualSnapshots].sort(
        (a, b) => new Date(a.snapshot_date) - new Date(b.snapshot_date)
    );

    const points = sorted.map(snapshot => ({
        label: formatDate(snapshot.snapshot_date),
        data: flatten(snapshot.data),
    }));
    points.push({ label: t('Current'), data: flatten(props.current) });

    const series = (label, color, yAxisID = 'y') => ({
        label: t(label),
        data: points.map(p => p.data.get(label)?.value ?? 0),
        borderColor: color,
        backgroundColor: color,
        pointRadius: 3,
        tension: 0.3,
        yAxisID,
    });

    return {
        labels: points.map(p => p.label),
        datasets: [
            series('Total visitors', '#6366f1'),
            series('Total sold tickets', '#22c55e'),
            series('Revenue', '#f59e0b', 'y1'),
        ],
    };
});

const trendOptions = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: { labels: { font: { family: 'inherit' }, boxWidth: 12 } },
    },
    scales: {
        y: { beginAtZero: true, position: 'left' },
        y1: {
            beginAtZero: true,
            position: 'right',
            grid: { drawOnChartArea: false },
            ticks: { callback: (v) => currencyFmt.format(v) },
        },
    },
};

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

const biSave = useBiSaveFeedback();

const createSnapshot = async () => {
    if (!newName.value || !newDate.value) return;
    const ok = await biSave.run(
        () => axios.post(route('projects.bi.snapshots.store', props.projectId), {
            name: newName.value,
            snapshot_date: newDate.value,
            scope: newScope.value,
        })
    );
    if (ok) {
        newName.value = '';
        newDate.value = '';
        emit('updated');
    }
};

const deleteSnapshot = async () => {
    const snapshotId = snapshotToDelete.value?.id;
    snapshotToDelete.value = null;
    if (!snapshotId) return;
    const ok = await biSave.run(
        () => axios.delete(route('projects.bi.snapshots.destroy', [props.projectId, snapshotId]))
    );
    if (ok) {
        emit('updated');
    }
};
</script>
