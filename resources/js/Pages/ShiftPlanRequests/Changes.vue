<template>
    <AppLayout :title="t('Shift plan Change list')">
        <div class="px-4 py-6 sm:px-6 lg:px-8 space-y-6">
            <!-- Wenn kein Gewerk ausgewählt ist: Hinweis -->
            <div
                v-if="!craft || !craft.id"
                class="relative flex min-h-[40vh] flex-col items-center justify-center rounded-2xl border border-dashed border-border bg-gradient-to-br from-surface-sunken to-surface-sunken px-6 py-10 text-center"
            >
                <div class="mx-auto mb-4 flex h-12 w-12 items-center justify-center rounded-full bg-white shadow-sm">
                    <span class="text-xl">🧱</span>
                </div>
                <h1 class="text-lg font-semibold text-text">
                    {{ t('Please select a craft') }}
                </h1>
                <p class="mt-2 max-w-md text-sm text-text-subtle">
                    {{ t('Select a craft to see all changes after commitment for this craft.') }}
                </p>

                <button
                    type="button"
                    class="mt-6 inline-flex items-center rounded-full bg-accent-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-accent-700 focus:outline-none focus:ring-2 focus:ring-accent-600 focus:ring-offset-2"
                    @click="openCraftSelector"
                >
                    {{ t('Select craft') }}
                </button>
            </div>

            <!-- Wenn Gewerk vorhanden: Header + Liste -->
            <div v-else class="space-y-6">
                <!-- Header-Karte mit Gewerk-Info & Kennzahlen -->
                <div class="rounded-2xl border border-border-subtle bg-white/80 shadow-sm backdrop-blur-sm">
                    <div class="flex flex-col gap-4 px-4 py-4 sm:flex-row sm:items-center sm:justify-between sm:px-6">
                        <div class="space-y-2">
                            <div class="inline-flex items-center gap-2 rounded-full bg-accent-50 px-3 py-1">
                                <span class="h-2 w-2 rounded-full bg-accent-600"></span>
                                <span class="text-xs font-medium text-accent-700">
                                    {{ t('Change list') }} – {{ craft.name }}
                                </span>
                            </div>
                            <div>
                                <h1 class="text-lg font-semibold text-text">
                                    {{ t('Changes after commitment') }}
                                </h1>
                                <p class="mt-1 max-w-2xl text-sm text-text-subtle">
                                    {{ t('All changes are displayed per person. If a shift with multiple people is changed, each person appears as a separate entry.') }}
                                </p>
                            </div>
                        </div>

                        <div class="flex flex-wrap items-center gap-8">
                            <div class="flex gap-3">
                                <div class="flex flex-col text-right">
                                    <span class="text-xs font-medium text-text-subtle">
                                        {{ t('Total changes') }}
                                    </span>
                                    <span class="text-lg font-semibold text-text">
                                        {{ totalChanges }}
                                    </span>
                                </div>
                                <div class="h-10 w-px self-center bg-border-subtle"></div>
                                <div class="flex flex-col text-right">
                                    <span class="text-xs font-medium text-text-subtle">
                                        {{ t('Open changes') }}
                                    </span>
                                    <span
                                        class="text-lg font-semibold"
                                        :class="pendingChanges > 0 ? 'text-warning' : 'text-success'"
                                    >
                                        {{ pendingChanges }}
                                    </span>
                                </div>
                            </div>

                            <div class="flex flex-wrap items-center gap-2">
                                <BaseUIButton
                                    v-if="pendingChanges > 0"
                                    type="button"
                                    is-add-button
                                    :label="t('Approve all open changes') + ' (' + pendingChanges + ')'"
                                    :use-translation="false"
                                    icon="IconChecks"
                                    @click="showAcknowledgeAllConfirm = true"
                                />
                                <BaseUIButton
                                    type="button"
                                    is-add-button
                                    label="Change craft"
                                    use-translation
                                    icon="IconRepeat"
                                    @click="openCraftSelector"
                                />
                            </div>
                        </div>
                    </div>

                    <!-- Filterleiste -->
                    <div class="border-t border-border-subtle px-4 py-3 sm:px-6">
                        <div class="flex flex-wrap items-center justify-between gap-3">
                            <div class="flex flex-wrap items-center gap-2">
                                <button
                                    v-for="filter in filters"
                                    :key="filter.value"
                                    type="button"
                                    class="inline-flex items-center gap-1.5 rounded-full px-3 py-1 text-xs font-medium transition"
                                    :class="activeFilter === filter.value ? 'bg-accent-600 text-white shadow-sm'
                                        : 'bg-surface-sunken text-text-muted hover:bg-border-subtle'"
                                    @click="changeFilter(filter.value)"
                                >
                                    <span>{{ t(filter.label) }}</span>
                                    <span
                                        v-if="filter.value === 'open' && pendingChanges > 0"
                                        class="rounded-full bg-white/20 px-1.5 text-[10px]"
                                    >
                                        {{ pendingChanges }}
                                    </span>
                                    <span
                                        v-if="filter.value === 'all' && totalChanges > 0"
                                        class="rounded-full bg-white/20 px-1.5 text-[10px]"
                                    >
                                        {{ totalChanges }}
                                    </span>
                                </button>

                                <!-- Intern/Extern-Filter über die betroffene Person -->
                                <div class="flex flex-wrap gap-2 border-l border-border-subtle pl-2">
                                    <button
                                        v-for="wt in workerTypeFilters"
                                        :key="wt.value"
                                        type="button"
                                        class="inline-flex items-center gap-1.5 rounded-full px-3 py-1 text-xs font-medium transition"
                                        :class="activeWorkerType === wt.value ? 'bg-accent-600 text-white shadow-sm'
                                            : 'bg-surface-sunken text-text-muted hover:bg-border-subtle'"
                                        @click="changeWorkerType(wt.value)"
                                    >
                                        {{ t(wt.label) }}
                                    </button>
                                </div>
                            </div>

                            <!-- Suche nach betroffener Einheit (serverseitig, über alle Seiten) -->
                            <div class="relative w-full sm:w-80">
                                <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-text-subtle">
                                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <circle cx="11" cy="11" r="7" />
                                        <line x1="21" y1="21" x2="16.65" y2="16.65" />
                                    </svg>
                                </span>
                                <input
                                    v-model="search"
                                    type="text"
                                    :placeholder="t('Search affected entity')"
                                    class="w-full rounded-full border border-border-subtle bg-white py-1.5 pl-9 pr-9 text-xs text-text-muted placeholder-text-subtle transition focus:border-accent-600 focus:outline-none focus:ring-1 focus:ring-accent-600"
                                >
                                <button
                                    v-if="search"
                                    type="button"
                                    class="absolute inset-y-0 right-0 flex items-center pr-3 text-text-subtle transition hover:text-text-muted"
                                    :aria-label="t('Reset')"
                                    @click="search = ''"
                                >
                                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <line x1="18" y1="6" x2="6" y2="18" />
                                        <line x1="6" y1="6" x2="18" y2="18" />
                                    </svg>
                                </button>
                            </div>
                        </div>

                        <p class="mt-2 text-xs text-text-subtle">
                            {{ t('Changes with pending approval are highlighted.') }}
                        </p>
                    </div>
                </div>

                <!-- Desktop: Tabelle -->
                <div class="hidden md:block">
                    <div class="overflow-hidden rounded-2xl border border-border-subtle bg-white/90 shadow-sm backdrop-blur-sm">
                        <table class="min-w-full divide-y divide-border-subtle">
                            <thead class="bg-surface-sunken">
                            <tr>
                                <th
                                    scope="col"
                                    class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-text-subtle sm:px-6"
                                >
                                    {{ t('Affected entity') }}
                                </th>
                                <th
                                    scope="col"
                                    class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-text-subtle sm:px-6"
                                >
                                    {{ t('Working time before') }}
                                </th>
                                <th
                                    scope="col"
                                    class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-text-subtle sm:px-6"
                                >
                                    {{ t('Working time after') }}
                                </th>
                                <th
                                    scope="col"
                                    class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-text-subtle sm:px-6"
                                >
                                    {{ t('Changed by') }}
                                </th>
                                <th
                                    scope="col"
                                    class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-text-subtle sm:px-6"
                                >
                                    {{ t('Changed at') }}
                                </th>
                                <th
                                    scope="col"
                                    class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-text-subtle sm:px-6"
                                >
                                    {{ t('Status') }}
                                </th>
                                <th scope="col" class="px-4 py-3 sm:px-6"></th>
                            </tr>
                            </thead>
                            <tbody class="divide-y divide-border-subtle">
                            <tr
                                v-for="change in pageChanges"
                                :key="change.id"
                                :class="[ !change.acknowledged ? 'bg-warning-surface/60' : 'bg-white',
                                    'transition hover:bg-accent-50/40'
                                ]"
                            >
                                <!-- Betroffene Entität (Person oder Schicht) -->
                                <td class="px-4 py-3 sm:px-6">
                                    <div class="flex items-center gap-3">
                                        <!-- Avatar (Bild oder Initialen) -->
                                        <div
                                            class="flex h-8 w-8 items-center justify-center overflow-hidden rounded-full bg-gradient-to-br from-accent-600 to-accent-600 text-xs font-semibold text-white shadow-sm"
                                        >
                                            <img
                                                v-if="change.profile_picture_url"
                                                :src="change.profile_picture_url"
                                                alt=""
                                                class="h-full w-full object-cover"
                                            >
                                            <span v-else>
                                                {{ getInitials(change.affected_name) }}
                                            </span>
                                        </div>
                                        <div class="flex flex-col">
                                            <span class="text-sm font-medium text-text">
                                                {{ change.affected_name || t('Affects shift') }}
                                            </span>
                                            <span class="text-xs text-text-subtle">
                                                {{ describeChange(change) }}
                                            </span>
                                        </div>
                                    </div>
                                </td>

                                <!-- Arbeitszeit vorher -->
                                <td class="px-4 py-3 text-sm text-text-muted sm:px-6">
                                    <div class="inline-flex items-center gap-1.5 rounded-full bg-surface-sunken px-2.5 py-1 text-xs leading-none text-text-muted">
                                        <span class="h-1.5 w-1.5 shrink-0 rounded-full bg-border-strong"></span>
                                        <span class="whitespace-nowrap">{{ change.before_label === 'free' ? $t('Free') : change.before_label }}</span>
                                    </div>
                                </td>

                                <!-- Arbeitszeit nachher -->
                                <td class="px-4 py-3 text-sm text-text-muted sm:px-6">
                                    <div class="inline-flex items-center gap-1.5 rounded-full bg-success-surface px-2.5 py-1 text-xs leading-none text-success">
                                        <span class="h-1.5 w-1.5 shrink-0 rounded-full bg-success"></span>
                                        <span class="whitespace-nowrap">{{ change.after_label === 'free' ? $t('Free') : change.after_label }}</span>
                                    </div>
                                </td>

                                <!-- Geändert von -->
                                <td class="px-4 py-3 text-sm text-text-muted sm:px-6">
                                    <div class="flex flex-col">
                                        <span class="text-sm text-text">
                                            {{ change.changed_by_name || '–' }}
                                        </span>
                                        <span class="text-xs text-text-subtle">
                                            {{ change.changed_at_formatted || '–' }}
                                        </span>
                                    </div>
                                </td>

                                <!-- Geändert am (nur Datum) -->
                                <td class="px-4 py-3 text-sm text-text-muted sm:px-6">
                                    <span class="whitespace-nowrap text-sm text-text-muted">
                                        {{ change.changed_at_formatted || '–' }}
                                    </span>
                                </td>

                                <!-- Status -->
                                <td class="px-4 py-3 sm:px-6">
                                    <span
                                        v-if="!change.acknowledged"
                                        class="inline-flex items-center gap-1.5 rounded-full bg-warning-surface px-2.5 py-1 text-xs font-medium leading-none text-warning"
                                    >
                                        <span class="h-1.5 w-1.5 shrink-0 rounded-full bg-warning"></span>
                                        <span class="whitespace-nowrap">{{ t('Changed after commitment') }}</span>
                                    </span>
                                    <span
                                        v-else
                                        class="inline-flex items-center gap-1.5 rounded-full bg-success-surface px-2.5 py-1 text-xs font-medium leading-none text-success"
                                    >
                                        <span class="h-1.5 w-1.5 shrink-0 rounded-full bg-success"></span>
                                        <span class="whitespace-nowrap">{{ t('Approval granted') }}</span>
                                    </span>
                                </td>

                                <!-- Aktion -->
                                <td class="px-4 py-3 text-right sm:px-6">
                                    <BaseUIButton
                                        v-if="!change.acknowledged"
                                        type="button"
                                        is-add-button
                                        @click="acknowledge(change)"
                                        label="Approve afterwards"
                                        use-translation
                                        icon="IconCheck"
                                    />
                                    <span v-else class="text-xs text-text-subtle">
                                        –
                                    </span>
                                </td>
                            </tr>

                            <tr v-if="pageChanges.length === 0">
                                <td colspan="7" class="px-4 py-8 text-center text-sm text-text-subtle sm:px-6">
                                    {{ t('No changes found for the current filter.') }}
                                </td>
                            </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Mobile: Kartenansicht -->
                <div class="space-y-3 md:hidden">
                    <div
                        v-for="change in pageChanges"
                        :key="change.id"
                        :class="[ 'rounded-2xl border px-4 py-3 shadow-sm',
                            !change.acknowledged
                                ? 'border-warning-border bg-warning-surface/60'
                                : 'border-border-subtle bg-white'
                        ]"
                    >
                        <div class="flex items-center gap-3">
                            <div
                                class="flex h-9 w-9 items-center justify-center overflow-hidden rounded-full bg-gradient-to-br from-accent-600 to-accent-600 text-xs font-semibold text-white shadow-sm"
                            >
                                <img
                                    v-if="change.profile_picture_url"
                                    :src="change.profile_picture_url"
                                    alt=""
                                    class="h-full w-full object-cover"
                                >
                                <span v-else>
                                    {{ getInitials(change.affected_name) }}
                                </span>
                            </div>
                            <div class="flex flex-1 flex-col">
                                <div class="flex items-center justify-between gap-2">
                                    <p class="text-sm font-medium text-text">
                                        {{ change.affected_name || t('Affects shift') }}
                                    </p>
                                    <span
                                        v-if="!change.acknowledged"
                                        class="inline-flex shrink-0 items-center whitespace-nowrap rounded-full bg-warning-surface px-2 py-0.5 text-[10px] font-medium leading-none text-warning"
                                    >
                                        {{ t('Changed after commitment') }}
                                    </span>
                                    <span
                                        v-else
                                        class="inline-flex shrink-0 items-center whitespace-nowrap rounded-full bg-success-surface px-2 py-0.5 text-[10px] font-medium leading-none text-success"
                                    >
                                        {{ t('Approval granted') }}
                                    </span>
                                </div>
                                <p class="mt-0.5 text-xs text-text-subtle">
                                    {{ describeChange(change) }}
                                </p>
                            </div>
                        </div>

                        <div class="mt-3 grid grid-cols-1 gap-2 text-xs text-text-muted">
                            <div class="flex items-center justify-between gap-2">
                                <span class="text-[11px] font-medium uppercase tracking-wide text-text-subtle">
                                    {{ t('Before') }}
                                </span>
                                <span class="inline-flex items-center gap-1.5 rounded-full bg-surface-sunken px-2.5 py-1 leading-none">
                                    <span class="h-1.5 w-1.5 shrink-0 rounded-full bg-border-strong"></span>
                                    <span class="whitespace-nowrap">{{ change.before_label === 'free' ? $t('Free') : change.before_label }}</span>
                                </span>
                            </div>
                            <div class="flex items-center justify-between gap-2">
                                <span class="text-[11px] font-medium uppercase tracking-wide text-text-subtle">
                                    {{ t('After') }}
                                </span>
                                <span class="inline-flex items-center gap-1.5 rounded-full bg-success-surface px-2.5 py-1 leading-none text-success">
                                    <span class="h-1.5 w-1.5 shrink-0 rounded-full bg-success"></span>
                                    <span class="whitespace-nowrap">{{ change.after_label === 'free' ? $t('Free') : change.after_label }}</span>
                                </span>
                            </div>
                        </div>

                        <div class="mt-3 flex items-center justify-between gap-2">
                            <div class="flex flex-col text-xs text-text-subtle">
                                <span class="font-medium text-text-muted">
                                    {{ change.changed_by_name || '–' }}
                                </span>
                                <span>
                                    {{ change.changed_at_formatted || '–' }}
                                </span>
                            </div>

                            <button
                                v-if="!change.acknowledged"
                                type="button"
                                class="inline-flex items-center rounded-full bg-accent-600 px-3 py-1.5 text-[11px] font-medium text-white shadow-sm hover:bg-accent-700 focus:outline-none focus:ring-2 focus:ring-accent-600 focus:ring-offset-1"
                                @click="acknowledge(change)"
                            >
                                {{ t('Approve') }}
                            </button>
                        </div>
                    </div>

                    <div
                        v-if="pageChanges.length === 0"
                        class="rounded-2xl border border-border-subtle bg-white px-4 py-6 text-center text-sm text-text-subtle"
                    >
                        {{ t('No changes after commitment have been recorded for this craft yet.') }}
                    </div>
                </div>

                <!-- Pagination -->
                <div
                    v-if="changes.last_page > 1"
                    class="flex flex-col items-center justify-between gap-3 rounded-2xl border border-border-subtle bg-white/80 px-4 py-3 shadow-sm sm:flex-row sm:px-6"
                >
                    <p class="text-xs text-text-subtle">
                        {{ t('Showing {0}–{1} of {2}', [changes.from || 0, changes.to || 0, changes.total || 0]) }}
                    </p>
                    <div class="flex items-center gap-2">
                        <button
                            type="button"
                            class="inline-flex items-center rounded-full border border-border-subtle px-3 py-1 text-xs font-medium text-text-muted transition hover:bg-surface-sunken disabled:cursor-not-allowed disabled:text-text-subtle"
                            :disabled="changes.current_page <= 1"
                            @click="goToPage(changes.current_page - 1)"
                        >
                            {{ t('Previous') }}
                        </button>
                        <span class="text-xs text-text-subtle">
                            {{ t('Page {0} of {1}', [changes.current_page, changes.last_page]) }}
                        </span>
                        <button
                            type="button"
                            class="inline-flex items-center rounded-full border border-border-subtle px-3 py-1 text-xs font-medium text-text-muted transition hover:bg-surface-sunken disabled:cursor-not-allowed disabled:text-text-subtle"
                            :disabled="changes.current_page >= changes.last_page"
                            @click="goToPage(changes.current_page + 1)"
                        >
                            {{ t('Next') }}
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Dein bestehendes Modal; API bleibt unverändert -->
        <CraftSelectorModal v-if="showCraftSelector" @close="showCraftSelector = false" />

        <!-- Bestätigung: alle offenen Änderungen der aktuellen Filterauswahl genehmigen -->
        <ArtworkBaseModal
            v-if="showAcknowledgeAllConfirm"
            :title="t('Approve all open changes')"
            :description="t('All open changes matching the current filter selection will be approved.')"
            @close="showAcknowledgeAllConfirm = false"
        >
            <div class="mt-4 text-sm text-text-muted">
                {{ t('Do you really want to approve all {0} open changes?', [pendingChanges]) }}
            </div>
            <div class="mt-6 flex justify-between">
                <BaseUIButton
                    type="button"
                    is-add-button
                    label="Approve"
                    use-translation
                    icon="IconChecks"
                    :processing="acknowledgingAll"
                    :disabled="acknowledgingAll"
                    @click="acknowledgeAll"
                />
                <BaseUIButton
                    type="button"
                    label="No, not really"
                    use-translation
                    icon="IconCancel"
                    @click="showAcknowledgeAllConfirm = false"
                />
            </div>
        </ArtworkBaseModal>
    </AppLayout>
</template>

<script setup>
import { computed, ref, watch } from 'vue';
import { useI18n } from 'vue-i18n';
import { router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import CraftSelectorModal from "@/Pages/ShiftPlanRequests/components/CraftSelectorModal.vue";
import BaseUIButton from "@/Artwork/Buttons/BaseUIButton.vue";
import ArtworkBaseModal from "@/Artwork/Modals/ArtworkBaseModal.vue";

const props = defineProps({
    craft: {
        type: Object,
        required: false,
        default: null,
    },
    allCrafts: {
        type: Array,
        required: true,
    },
    // Paginator-Objekt (Laravel LengthAwarePaginator): { data, current_page, last_page,
    // per_page, total, from, to, prev_page_url, next_page_url, ... }
    changes: {
        type: Object,
        required: true,
    },
    // Aktiver serverseitiger Filter: 'all' | 'open' | 'ack'
    filter: {
        type: String,
        default: 'all',
    },
    // Aktueller serverseitiger Suchbegriff (betroffene Einheit)
    search: {
        type: String,
        default: '',
    },
    // Aktiver Intern/Extern-Filter: 'all' | 'internal' | 'external'
    workerType: {
        type: String,
        default: 'all',
    },
    // Zähler über alle Seiten hinweg (unabhängig vom aktiven Filter)
    totalCount: {
        type: Number,
        default: 0,
    },
    pendingCount: {
        type: Number,
        default: 0,
    },
});

const { t } = useI18n();

const showCraftSelector = ref(false);

// Filter wird serverseitig ausgewertet; der aktive Wert kommt als Prop.
const activeFilter = computed(() => props.filter);

const filters = [
    { value: 'all',  label: 'All changes' },
    { value: 'open', label: 'Open changes' },
    { value: 'ack',  label: 'Approval granted' },
];

// Intern/Extern-Filter (serverseitig): intern = normale User, extern = Freelancer,
// Dienstleister und User mit "als Freelancer anzeigen".
const activeWorkerType = computed(() => props.workerType);

const workerTypeFilters = [
    { value: 'all',      label: 'All' },
    { value: 'internal', label: 'Internal' },
    { value: 'external', label: 'External' },
];

const changeWorkerType = (value) => {
    if (value === props.workerType) {
        return;
    }
    router.reload({
        data: { worker_type: value, page: 1 },
        preserveScroll: true,
    });
};

// Zähler kommen serverseitig (über alle Seiten), nicht mehr aus dem geladenen Array.
const totalChanges = computed(() => props.totalCount);
const pendingChanges = computed(() => props.pendingCount);

// Nur die Datensätze der aktuellen Seite.
const pageChanges = computed(() => props.changes?.data ?? []);

// Filterwechsel: serverseitig neu laden, Seite auf 1 zurücksetzen.
// (router.reload behält die übrige Query-String – inkl. search – bei.)
const changeFilter = (value) => {
    if (value === props.filter) {
        return;
    }
    router.reload({
        data: { filter: value, page: 1 },
        preserveScroll: true,
    });
};

// Suche: lokaler Eingabewert, serverseitig (debounced) über die gesamte Datenmenge.
const search = ref(props.search ?? '');

let searchTimer = null;
watch(search, (value) => {
    if (searchTimer) {
        window.clearTimeout(searchTimer);
    }
    searchTimer = window.setTimeout(() => {
        router.reload({
            // Bei neuer Suche zurück auf Seite 1; Filter bleibt über die URL erhalten.
            data: { search: value.trim(), page: 1 },
            preserveScroll: true,
            // Komponente erhalten, damit der Fokus im Suchfeld beim Tippen bleibt.
            preserveState: true,
        });
    }, 300);
});

// Seitennavigation über die Seitennummer (relativ), NICHT über die absolute
// Paginator-URL. Hinter einem Reverse-Proxy mit extern terminiertem TLS erzeugt Laravel
// sonst http://-URLs; ein XHR von der https-Seite dorthin wird als Mixed Content
// blockiert ("Network Error"). router.reload nutzt die aktuelle (relative) URL und
// behält Filter & Suche im Query-String bei.
const goToPage = (page) => {
    if (! page || page < 1 || page > (props.changes?.last_page ?? 1)) {
        return;
    }
    router.reload({
        data: { page },
        preserveScroll: true,
        preserveState: true,
    });
};

const describeChange = (change) => {
    const fieldChanges = change.field_changes || {};

    // Relevante Keys (ohne _initial)
    const keys = Object.keys(fieldChanges).filter((k) => k !== '_initial');

    // Mapping Feldname -> Label (alles über $t())
    const fieldLabel = (key) => {
        switch (key) {
            case 'start':
                return t('Start time');
            case 'end':
                return t('End time');
            case 'break_minutes':
                return t('Break');
            case 'qualifications':
                return t('Qualifications');
            case 'global_qualifications':
                return t('Global qualifications');
            case 'assignment':
                return t('Assignment');
            case 'individual_time':
                return t('Individual working time');
            case 'worker_short_description':
                return t('Short description');
            default:
                return key;
        }
    };

    // Liste der Feld-Labels (ohne assignment, das behandeln wir separat)
    const changedFieldLabels = keys
        .filter((k) => k !== 'assignment')
        .map((k) => fieldLabel(k));

    const fieldList = changedFieldLabels.join(', ');

    switch (change.change_type) {
        case 'user_removed_from_shift':
            if (change.affected_name) {
                return t('User {0} removed from shift', [change.affected_name]);
            }
            return t('User removed from shift');

        case 'user_added_to_shift':
            if (change.affected_name) {
                return t('User {0} added to shift', [change.affected_name]);
            }
            return t('User added to shift');

        case 'updated':
            if (fieldList) {
                return t('Shift updated ({0})', [fieldList]);
            }
            return t('Shift updated');

        case 'revert':
            if (fieldList) {
                return t('Change reverted ({0})', [fieldList]);
            }
            return t('Change reverted');

        default:
            // Fallback: einfach den change_type übersetzen, falls es einen Key gibt
            return t(change.change_type);
    }
};


const openCraftSelector = () => {
    showCraftSelector.value = true;
};

const acknowledge = (change) => {
    router.post(
        route('committed-shift-changes.acknowledge', change.id),
        {},
        {
            preserveScroll: true,
            preserveState: true,
        }
    );
};

// Bulk-Genehmigung: alle offenen Änderungen der aktuellen Filterauswahl
// (Gewerk + Suche + Intern/Extern) in einem Request genehmigen.
const showAcknowledgeAllConfirm = ref(false);
const acknowledgingAll = ref(false);

const acknowledgeAll = () => {
    if (acknowledgingAll.value) {
        return;
    }
    acknowledgingAll.value = true;
    router.post(
        route('committed-shift-changes.acknowledge-all'),
        {
            craft_id: props.craft?.id,
            search: search.value.trim(),
            worker_type: props.workerType,
        },
        {
            preserveScroll: true,
            onFinish: () => {
                acknowledgingAll.value = false;
                showAcknowledgeAllConfirm.value = false;
            },
        }
    );
};

const getInitials = (name) => {
    if (!name) return '?';
    return name
        .split(' ')
        .filter(Boolean)
        .slice(0, 2)
        .map(part => part[0]?.toUpperCase())
        .join('');
};
</script>

<style scoped>
/* keine extra Styles nötig, Tailwind übernimmt alles */
</style>
