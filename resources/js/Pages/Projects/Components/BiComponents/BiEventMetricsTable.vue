<template>
    <div>
        <!-- Toolbar: collapse + filter -->
        <div class="flex items-center justify-between mb-2">
            <button type="button" @click="expanded = !expanded" class="flex items-center gap-2 text-sm font-medium text-text-muted print:hidden">
                <IconChevronDown class="size-4 transition-transform" :class="{ '-rotate-90': !expanded }" />
                {{ $t('Entries per event') }} <span class="text-text-subtle">({{ displayedEvents.length }})</span>
            </button>
            <div class="flex items-center gap-2 print:hidden">
                <!-- Standard: nur Publikumstermine (BI-Tag Vorstellung/Veranstaltungstag) — Proben
                     und Aufbauten bekommen keine Besucherzahlen -->
                <button
                    v-if="hasAudienceEvents"
                    type="button"
                    class="inline-flex items-center gap-1.5 rounded-full border px-2.5 py-1 text-xs font-medium transition"
                    :class="audienceOnly ? 'border-accent-200 bg-accent-50 text-accent-700 hover:bg-accent-100' : 'border-border-subtle text-text-muted hover:bg-surface-sunken'"
                    @click="audienceOnly = !audienceOnly"
                >
                    <IconUsers class="size-3.5" />
                    {{ audienceOnly ? `${$t('Audience events only')} (${audienceEvents.length})` : $t('Show all events') }}
                </button>
                <button
                    type="button"
                    @click="showFilters = !showFilters"
                    class="inline-flex items-center gap-1.5 rounded-md border border-border-subtle px-2.5 py-1 text-xs text-text-muted hover:bg-surface-sunken transition"
                    :class="{ 'bg-surface-sunken border-border': showFilters || hasActiveFilter }"
                >
                    <IconFilter class="size-4" />
                    {{ $t('Filter') }}
                </button>
            </div>
        </div>

        <div v-show="expanded" class="print:!block">
            <!-- Ausfüllhilfe ist sonst unsichtbar, bis man zufällig eine Zeile markiert -->
            <p v-if="canEdit && selectedIds.size === 0 && !hasBulkInput && displayedEvents.length > 1" class="mb-2 text-[11px] text-text-subtle print:hidden">
                {{ $t('Select several events to enter values at once.') }}
            </p>
            <!-- Filter bar -->
            <div v-if="showFilters" class="mb-3 flex flex-wrap items-end gap-3 rounded-md bg-surface-sunken p-3 print:hidden">
                <ArtworkBaseListbox
                    class="w-56"
                    :model-value="selectedRoom"
                    @update:model-value="room => filterRoomId = room?.id ?? null"
                    :items="rooms"
                    by="id"
                    option-label="name"
                    :label="$t('Room')"
                    :placeholder="$t('All rooms')"
                />
                <BaseInput id="bi_event_search" v-model="search" :label="$t('Search event')" class="w-56" />
                <button v-if="hasActiveFilter" type="button" @click="clearFilters" class="pb-2 text-xs text-text-subtle hover:text-text-muted">
                    {{ $t('Reset') }}
                </button>
            </div>

            <!-- Ausfüllhilfe: Werte auf ausgewählte Termine anwenden -->
            <div
                v-if="canEdit && (selectedIds.size > 0 || hasBulkInput)"
                class="mb-3 flex flex-wrap items-end gap-3 rounded-md border border-accent-200 bg-accent-50/60 p-3 print:hidden"
            >
                <span class="pb-2 text-xs font-medium text-accent-700 whitespace-nowrap">
                    {{ selectedIds.size }} {{ $t('events selected') }}
                </span>
                <BaseInput
                    v-for="field in fields"
                    :key="'bulk_' + field.key"
                    type="number"
                    :id="'bi_bulk_' + field.key"
                    v-model.number="bulkValues[field.key]"
                    :label="(field.translate ?? true) ? $t(field.label) : field.label"
                    without-translation
                    :min="0"
                    :step="field.key === 'revenue' ? 0.01 : 1"
                    class="w-32"
                />
                <BaseUIButton
                    :label="$t('Apply to selection')"
                    :disabled="bulkSaving || !hasBulkInput"
                    hide-icon
                    @click="applyBulk"
                />
                <button type="button" class="pb-2 text-xs text-text-subtle hover:text-text-muted" @click="clearSelection">
                    {{ $t('Clear selection') }}
                </button>
                <p class="w-full text-[11px] text-accent-700/70 -mt-1">
                    {{ $t('Empty fields are skipped. Tip: use the copy icon in a row to take over its values.') }}
                </p>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-border-subtle text-sm">
                    <thead>
                        <tr>
                            <th v-if="canEdit" class="py-2 pr-2 w-8 print:hidden">
                                <BaseCheckbox
                                    :id="'bi_select_all_' + scope"
                                    :model-value="allDisplayedSelected"
                                    @update:model-value="toggleAll"
                                />
                            </th>
                            <th
                                v-for="col in columns"
                                :key="col.key"
                                class="py-2 px-3 text-left font-semibold text-text cursor-pointer select-none whitespace-nowrap first:pl-0"
                                @click="sortByColumn(col.key)"
                            >
                                <span class="inline-flex items-center gap-1">
                                    {{ col.translate ? $t(col.label) : col.label }}
                                    <IconChevronUp v-if="sortKey === col.key && sortAsc" class="size-3.5" />
                                    <IconChevronDown v-else-if="sortKey === col.key" class="size-3.5" />
                                    <IconArrowsSort v-else class="size-3.5 text-text-subtle" />
                                </span>
                            </th>
                            <th v-if="canEdit" class="w-8 print:hidden"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-border-subtle">
                        <tr
                            v-for="event in displayedEvents"
                            :key="event.id"
                            class="hover:bg-surface-sunken"
                            :class="{ 'bg-accent-50': scope === 'actual' && event.id === latestPastEventId,
                                'opacity-50': isFutureEvent(event),
                            }"
                            :title="isFutureEvent(event) ? $t('Event lies in the future') : null"
                        >
                            <td v-if="canEdit" class="py-2 pr-2 print:hidden">
                                <BaseCheckbox
                                    :id="'bi_select_' + scope + '_' + event.id"
                                    :model-value="selectedIds.has(event.id)"
                                    @update:model-value="checked => toggleRow(event.id, checked)"
                                />
                            </td>
                            <td class="py-2 pl-0 pr-3 text-text-muted">
                                <span class="inline-flex items-center gap-2">
                                    {{ event.name }}
                                    <!-- Erfassungs-Anker nur im Ist: für Planwerte gibt es kein "zuletzt stattgefunden" -->
                                    <span
                                        v-if="scope === 'actual' && event.id === latestPastEventId"
                                        class="rounded-full bg-accent-100 px-2 py-0.5 text-[10px] font-medium text-accent-700 whitespace-nowrap print:hidden"
                                        v-tooltip.top="{ value: $t('The most recent event that already took place — the usual starting point for entering figures after a performance.'), appendTo: 'body', class: 'aw-tooltip' }"
                                    >
                                        {{ $t('Most recent event') }}
                                    </span>
                                </span>
                            </td>
                            <td class="py-2 px-3 text-text-subtle whitespace-nowrap">{{ event.start_time }}</td>
                            <td class="py-2 px-3 text-text-subtle">{{ event.room_name }}</td>
                            <td v-for="field in fields" :key="field.key" class="py-2 px-3">
                                <BaseInput
                                    v-if="canEdit"
                                    type="number"
                                    :id="'bi_cell_' + scope + '_' + event.id + '_' + field.key"
                                    :label="(field.translate ?? true) ? $t(field.label) : field.label"
                                    without-translation
                                    :show-label="false"
                                    :min="0"
                                    :step="field.key === 'revenue' ? 0.01 : 1"
                                    :data-bi-cell="field.key"
                                    :model-value="cellValue(event.id, field.key)"
                                    :input-classes="failedCells.has(draftKey(event.id, field.key)) ? 'border-danger! ring-1 ring-danger!' : ''"
                                    :error="failedCells.has(draftKey(event.id, field.key)) ? ' ' : ''"
                                    @update:model-value="value => setDraft(event.id, field.key, value)"
                                    @change="saveEventValue(event.id, field.key, $event.target.value)"
                                    @keydown.enter.prevent="focusNextRow(event.id, field.key)"
                                    class="w-24"
                                />
                                <span v-else class="text-text-muted">{{ getEventValue(event.id, field.key) ?? '–' }}</span>
                            </td>
                            <td v-if="showOccupancy" class="py-2 px-3">
                                <div v-if="occupancyFor(event) !== null" class="flex items-center gap-2 min-w-28">
                                    <div class="h-1.5 w-16 rounded-full bg-surface-sunken overflow-hidden shrink-0">
                                        <div
                                            class="h-full rounded-full"
                                            :class="occupancyBarClass(occupancyFor(event))"
                                            :style="{ width: Math.min(occupancyFor(event), 100) + '%' }"
                                        ></div>
                                    </div>
                                    <span class="text-xs text-text-muted whitespace-nowrap">{{ occupancyFor(event).toFixed(0) }} %</span>
                                </div>
                                <span v-else class="text-text-subtle">–</span>
                            </td>
                            <td v-if="canEdit" class="py-2 px-1 text-right print:hidden">
                                <button
                                    type="button"
                                    class="text-text-subtle hover:text-accent-600 transition"
                                    v-tooltip.top="{ value: $t('Copy values of this row into the fill helper'), appendTo: 'body', class: 'aw-tooltip' }"
                                    @click="copyRowToBulk(event)"
                                >
                                    <IconCopy class="size-4" />
                                </button>
                            </td>
                        </tr>
                        <tr v-if="displayedEvents.length === 0">
                            <td :colspan="totalColumnCount" class="py-4 text-center text-text-subtle">{{ $t('No events found.') }}</td>
                        </tr>
                    </tbody>
                    <tfoot v-if="displayedEvents.length > 0">
                        <tr class="border-t-2 border-border">
                            <td v-if="canEdit" class="print:hidden"></td>
                            <td colspan="3" class="py-2 pl-0 pr-3 font-semibold text-text">{{ $t('Sum') }}</td>
                            <td v-for="field in fields" :key="field.key" class="py-2 px-3 font-semibold text-text">
                                {{ formatSum(field, sums[field.key]) }}
                            </td>
                            <td v-if="showOccupancy" class="py-2 px-3"></td>
                            <td v-if="canEdit" class="print:hidden"></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
</template>

<script setup>
import { computed, reactive, ref, watch } from 'vue';
import { IconChevronDown, IconChevronUp, IconArrowsSort, IconFilter, IconCopy, IconUsers } from '@tabler/icons-vue';
import BaseInput from '@/Artwork/Inputs/BaseInput.vue';
import BaseCheckbox from '@/Artwork/Inputs/BaseCheckbox.vue';
import BaseUIButton from '@/Artwork/Buttons/BaseUIButton.vue';
import ArtworkBaseListbox from '@/Artwork/Listbox/ArtworkBaseListbox.vue';
import { useBiSaveFeedback } from '@/Composeables/BiSaveFeedback.js';

const props = defineProps({
    eventData: { type: Array, default: () => [] },
    projectEvents: { type: Array, default: () => [] },
    canEdit: { type: Boolean, default: false },
    projectId: { type: Number, required: true },
    // [{ key: 'visitors', label: 'Visitors' }, ...] — nur Kennzahlen im Pro-Termin-Modus.
    // Kategorie-Spalten tragen zusätzlich categoryId (+ translate:false beim Label)
    // und speichern über den category-values-Endpoint statt upsert-event-data.
    fields: { type: Array, required: true },
    // Pro-Termin-Werte der Besucher*innen-Kategorien (scope actual, event_id gesetzt)
    categoryValues: { type: Array, default: () => [] },
    showOccupancy: { type: Boolean, default: false },
    // room_id → effektive Kapazität (Override oder Raum-Default)
    effectiveCapacities: { type: Object, default: () => ({}) },
    // Plan/Ist-Dimension: bestimmt, in welchen Datensatz gespeichert wird
    scope: { type: String, default: 'actual' },
});

const emit = defineEmits(['updated']);

const biSave = useBiSaveFeedback();

const expanded = ref(true);
const showFilters = ref(false);
const sortKey = ref('date');
// Neueste zuerst: nach einer Vorstellung erfasst man den jüngsten Termin
const sortAsc = ref(false);
const filterRoomId = ref(null);
const search = ref('');

const currencyFmt = new Intl.NumberFormat('de-DE', { style: 'currency', currency: 'EUR' });
const numberFmt = new Intl.NumberFormat('de-DE');

const columns = computed(() => [
    { key: 'name', label: 'Event', translate: true },
    { key: 'date', label: 'Date', translate: true },
    { key: 'room', label: 'Room', translate: true },
    ...props.fields.map(f => ({ key: f.key, label: f.label, translate: f.translate ?? true })),
    ...(props.showOccupancy ? [{ key: 'occupancy', label: 'Occupancy rate', translate: true }] : []),
]);

const rooms = computed(() => {
    const map = new Map();
    props.projectEvents.forEach((event) => {
        if (event.room_id && !map.has(event.room_id)) {
            map.set(event.room_id, { id: event.room_id, name: event.room_name });
        }
    });
    return [...map.values()];
});

const selectedRoom = computed(() => rooms.value.find(r => r.id === filterRoomId.value) ?? null);
const hasActiveFilter = computed(() => filterRoomId.value !== null || search.value.trim() !== '');

// Publikumstermine (Terminart mit BI-Tag Vorstellung/Veranstaltungstag) — Standardansicht,
// sobald es solche gibt; ohne Tag-Zuordnung bleibt die volle Liste
const audienceEvents = computed(() => props.projectEvents.filter(e => e.is_audience_event));
const hasAudienceEvents = computed(() => audienceEvents.value.length > 0);
// Standard nur dann "Publikumstermine", wenn keine erfassten Werte an anderen Terminen
// hängen — sonst sind die Tags offensichtlich unvollständig und der Filter würde
// genau die Termine verstecken, an denen gearbeitet wird
const defaultAudienceOnly = computed(() => {
    if (!hasAudienceEvents.value) return false;
    const audienceIds = new Set(audienceEvents.value.map(e => e.id));
    const hasValue = (entry) => ['visitors', 'sold_tickets', 'revenue'].some(
        key => entry[key] !== null && entry[key] !== undefined
    );
    return !props.eventData.some(entry => hasValue(entry) && !audienceIds.has(entry.event_id));
});
const audienceOnlyChoice = ref(null); // null = noch keine bewusste Wahl → Standard
const audienceOnly = computed({
    get: () => audienceOnlyChoice.value ?? defaultAudienceOnly.value,
    set: (value) => { audienceOnlyChoice.value = value; },
});
const baseEvents = computed(() => (audienceOnly.value && hasAudienceEvents.value) ? audienceEvents.value : props.projectEvents);

// Zellen, deren letzter Save fehlgeschlagen ist (roter Rahmen, bis ein Save durchgeht)
const failedCells = ref(new Set());
const markCell = (key, failed) => {
    const next = new Set(failedCells.value);
    if (failed) {
        next.add(key);
    } else {
        next.delete(key);
    }
    failedCells.value = next;
};

const entriesByEventId = computed(() => new Map(props.eventData.map(e => [e.event_id, e])));

const fieldByKey = computed(() => new Map(props.fields.map(f => [f.key, f])));

const categoryValueMap = computed(() => {
    const map = new Map();
    props.categoryValues.forEach((value) => {
        if (value.event_id !== null && value.event_id !== undefined) {
            map.set(`${value.event_id}:${value.bi_audience_category_id}`, value.quantity);
        }
    });
    return map;
});

const getEventValue = (eventId, fieldKey) => {
    const field = fieldByKey.value.get(fieldKey);
    if (field?.categoryId) {
        return categoryValueMap.value.get(`${eventId}:${field.categoryId}`) ?? null;
    }
    const entry = entriesByEventId.value.get(eventId);
    return entry ? entry[fieldKey] : null;
};

// --- Draft-Puffer: laufende Eingaben überleben den Daten-Refetch des Parents ---
// Ohne Puffer setzt jede Prop-Aktualisierung (nach dem Save einer ANDEREN Zelle)
// den DOM-Wert der gerade getippten Zelle zurück.

const drafts = reactive({});

const draftKey = (eventId, fieldKey) => `${eventId}:${fieldKey}`;

const setDraft = (eventId, fieldKey, value) => {
    drafts[draftKey(eventId, fieldKey)] = value;
};

const cellValue = (eventId, fieldKey) => {
    const key = draftKey(eventId, fieldKey);
    return key in drafts ? drafts[key] : getEventValue(eventId, fieldKey);
};

const valuesEqual = (draft, propValue) => {
    const draftEmpty = draft === '' || draft === null || draft === undefined;
    const propEmpty = propValue === null || propValue === undefined;
    if (draftEmpty || propEmpty) return draftEmpty && propEmpty;
    return Number(draft) === Number(propValue);
};

// Draft verwerfen, sobald der Server denselben Wert liefert (Save angekommen)
watch(() => [props.eventData, props.categoryValues], () => {
    Object.keys(drafts).forEach((key) => {
        const [eventId, fieldKey] = key.split(':');
        if (valuesEqual(drafts[key], getEventValue(Number(eventId), fieldKey))) {
            delete drafts[key];
        }
    });
});

const isFutureEvent = (event) => {
    const ts = parseDate(event.start_time);
    return ts > Date.now();
};

// Enter springt zum chronologisch NÄCHSTEN Termin derselben Spalte (Serien-Erfassung
// in der Reihenfolge, in der die Vorstellungen stattfanden) — bei absteigender
// Datumssortierung ist das die Zeile darüber. Der Fokuswechsel committet die
// aktuelle Zelle über das change-Event.
const focusNextRow = (eventId, fieldKey) => {
    const row = document.activeElement?.closest('tr');
    const forwardIsDown = !(sortKey.value === 'date' && !sortAsc.value);
    const sibling = forwardIsDown ? row?.nextElementSibling : row?.previousElementSibling;
    const next = sibling?.querySelector(`input[data-bi-cell="${fieldKey}"]`);
    if (next) {
        next.focus();
        next.select();
    } else {
        document.activeElement?.blur();
    }
};

const parseDate = (value) => {
    if (!value) return 0;
    const [datePart, timePart = '00:00'] = String(value).split(' ');
    const [day, month, year] = datePart.split('.');
    const [hour, minute] = timePart.split(':');
    return new Date(+year, (+month || 1) - 1, +day || 1, +hour || 0, +minute || 0).getTime();
};

const occupancyFor = (event) => {
    const sold = getEventValue(event.id, 'sold_tickets');
    const capacity = props.effectiveCapacities[event.room_id];
    if (sold === null || sold === undefined || !capacity) return null;
    return (Number(sold) / Number(capacity)) * 100;
};

const occupancyBarClass = (value) => {
    if (value >= 90) return 'bg-success';
    if (value >= 50) return 'bg-accent-600';
    return 'bg-warning';
};

const sortValue = (event, key) => {
    switch (key) {
        case 'date': return parseDate(event.start_time);
        case 'room': return (event.room_name || '').toLowerCase();
        case 'occupancy': return occupancyFor(event) ?? -1;
        case 'name': return (event.name || '').toLowerCase();
        default: return parseFloat(getEventValue(event.id, key)) || 0;
    }
};

const displayedEvents = computed(() => {
    let list = [...baseEvents.value];

    if (filterRoomId.value !== null) {
        list = list.filter(e => e.room_id === filterRoomId.value);
    }

    const term = search.value.trim().toLowerCase();
    if (term) {
        list = list.filter(e => (e.name || '').toLowerCase().includes(term));
    }

    list.sort((a, b) => {
        const av = sortValue(a, sortKey.value);
        const bv = sortValue(b, sortKey.value);
        if (av < bv) return sortAsc.value ? -1 : 1;
        if (av > bv) return sortAsc.value ? 1 : -1;
        return 0;
    });

    return list;
});

// Der zuletzt vergangene Termin — typischer Erfassungs-Einstieg nach einer Vorstellung
const latestPastEventId = computed(() => {
    const now = Date.now();
    let best = null;
    let bestTs = -Infinity;
    // Anker innerhalb der Publikumstermine — "Abbau" ist kein Erfassungs-Einstieg
    baseEvents.value.forEach((event) => {
        const ts = parseDate(event.start_time);
        if (ts && ts <= now && ts > bestTs) {
            bestTs = ts;
            best = event.id;
        }
    });
    return best;
});

const sums = computed(() => {
    const result = {};
    props.fields.forEach((field) => {
        result[field.key] = displayedEvents.value.reduce(
            (sum, event) => sum + (parseFloat(getEventValue(event.id, field.key)) || 0),
            0
        );
    });
    return result;
});

const formatSum = (field, value) => field.key === 'revenue' ? currencyFmt.format(value ?? 0) : numberFmt.format(value ?? 0);

const sortByColumn = (key) => {
    if (sortKey.value === key) {
        sortAsc.value = !sortAsc.value;
    } else {
        sortKey.value = key;
        sortAsc.value = true;
    }
};

const clearFilters = () => {
    filterRoomId.value = null;
    search.value = '';
};

const totalColumnCount = computed(() => columns.value.length + (props.canEdit ? 2 : 0));

// --- Ausfüllhilfe (Multiselect + Werte auf Auswahl anwenden) ---

const selectedIds = ref(new Set());
const bulkValues = reactive({});
const bulkSaving = ref(false);

const hasBulkInput = computed(() => props.fields.some(
    field => bulkValues[field.key] !== null && bulkValues[field.key] !== undefined && bulkValues[field.key] !== ''
));

const allDisplayedSelected = computed(() =>
    displayedEvents.value.length > 0 && displayedEvents.value.every(e => selectedIds.value.has(e.id))
);

const toggleRow = (eventId, checked) => {
    const next = new Set(selectedIds.value);
    if (checked) {
        next.add(eventId);
    } else {
        next.delete(eventId);
    }
    selectedIds.value = next;
};

const toggleAll = (checked) => {
    selectedIds.value = checked ? new Set(displayedEvents.value.map(e => e.id)) : new Set();
};

const clearSelection = () => {
    selectedIds.value = new Set();
    props.fields.forEach(field => { bulkValues[field.key] = null; });
};

const copyRowToBulk = (event) => {
    props.fields.forEach((field) => {
        bulkValues[field.key] = getEventValue(event.id, field.key);
    });
};

const applyBulk = async () => {
    if (!hasBulkInput.value || selectedIds.value.size === 0) return;
    bulkSaving.value = true;
    const data = {};
    const categoryEntries = [];
    props.fields.forEach((field) => {
        const value = bulkValues[field.key];
        if (value === null || value === undefined || value === '') return;
        if (field.categoryId) {
            categoryEntries.push({ categoryId: field.categoryId, quantity: Number(value) });
        } else {
            data[field.key] = Number(value);
        }
    });
    const ok = await biSave.run(async () => {
        // allSettled statt all: bei Teilfehlern wissen, was durchging —
        // Auswahl bleibt dann stehen, der Refetch zeigt die gespeicherten Zeilen
        const requests = [];
        if (Object.keys(data).length > 0) {
            [...selectedIds.value].forEach(eventId => requests.push(
                axios.put(route('projects.bi.upsert-event-data', [props.projectId, eventId]), { ...data, scope: props.scope })
            ));
        }
        if (categoryEntries.length > 0) {
            const values = [...selectedIds.value].flatMap(eventId => categoryEntries.map(entry => ({
                bi_audience_category_id: entry.categoryId,
                event_id: eventId,
                quantity: entry.quantity,
            })));
            requests.push(axios.put(route('projects.bi.upsert-category-values', props.projectId), { values, scope: props.scope }));
        }
        const results = await Promise.allSettled(requests);
        const failed = results.filter(r => r.status === 'rejected');
        if (failed.length > 0) {
            throw new Error(`${failed.length}/${results.length} bulk saves failed`);
        }
    });
    if (ok) {
        clearSelection();
    }
    emit('updated');
    bulkSaving.value = false;
};

const saveEventValue = async (eventId, fieldKey, value) => {
    const field = fieldByKey.value.get(fieldKey);
    const parsed = value === '' ? null : Number(value);
    const cellKey = draftKey(eventId, fieldKey);
    const ok = await biSave.run(() => {
        if (field?.categoryId) {
            return axios.put(route('projects.bi.upsert-category-values', props.projectId), {
                scope: props.scope,
                values: [{
                    bi_audience_category_id: field.categoryId,
                    event_id: eventId,
                    quantity: parsed,
                }],
            });
        }
        return axios.put(
            route('projects.bi.upsert-event-data', [props.projectId, eventId]),
            { [fieldKey]: parsed, scope: props.scope }
        );
    });
    // Fehlgeschlagene Zelle rot markieren — der Draft bleibt stehen, der Grund
    // steht in der Status-Pille; nächster erfolgreicher Save hebt die Markierung auf
    markCell(cellKey, !ok);
    if (ok) {
        emit('updated');
    }
};
</script>
