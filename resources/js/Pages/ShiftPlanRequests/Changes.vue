<template>
    <AppLayout :title="t('Change list')">
        <div class="px-4 py-6 sm:px-6 lg:px-8 space-y-6">
            <!-- Wenn kein Gewerk ausgewählt ist: Hinweis -->
            <div
                v-if="!craft || !craft.id"
                class="relative flex min-h-[40vh] flex-col items-center justify-center rounded-2xl border border-dashed border-gray-300 bg-gradient-to-br from-gray-50 to-gray-100 px-6 py-10 text-center"
            >
                <div class="mx-auto mb-4 flex h-12 w-12 items-center justify-center rounded-full bg-white shadow-sm">
                    <span class="text-xl">🧱</span>
                </div>
                <h1 class="text-lg font-semibold text-gray-900">
                    {{ t('Please select a craft') }}
                </h1>
                <p class="mt-2 max-w-md text-sm text-gray-500">
                    {{ t('Select a craft to see all changes after commitment for this craft.') }}
                </p>

                <button
                    type="button"
                    class="mt-6 inline-flex items-center rounded-full bg-indigo-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
                    @click="openCraftSelector"
                >
                    {{ t('Select craft') }}
                </button>
            </div>

            <!-- Wenn Gewerk vorhanden: Header + Liste -->
            <div v-else class="space-y-6">
                <!-- Header-Karte mit Gewerk-Info & Kennzahlen -->
                <div class="rounded-2xl border border-gray-200 bg-white/80 shadow-sm backdrop-blur-sm">
                    <div class="flex flex-col gap-4 px-4 py-4 sm:flex-row sm:items-center sm:justify-between sm:px-6">
                        <div class="space-y-2">
                            <div class="inline-flex items-center gap-2 rounded-full bg-indigo-50 px-3 py-1">
                                <span class="h-2 w-2 rounded-full bg-indigo-500"></span>
                                <span class="text-xs font-medium text-indigo-700">
                                    {{ t('Change list') }} – {{ craft.name }}
                                </span>
                            </div>
                            <div>
                                <h1 class="text-lg font-semibold text-gray-900">
                                    {{ t('Changes after commitment') }}
                                </h1>
                                <p class="mt-1 max-w-2xl text-sm text-gray-500">
                                    {{ t('All changes are displayed per person. If a shift with multiple people is changed, each person appears as a separate entry.') }}
                                </p>
                            </div>
                        </div>

                        <div class="flex flex-wrap items-center gap-8">
                            <div class="flex gap-3">
                                <div class="flex flex-col text-right">
                                    <span class="text-xs font-medium text-gray-500">
                                        {{ t('Total changes') }}
                                    </span>
                                    <span class="text-lg font-semibold text-gray-900">
                                        {{ totalChanges }}
                                    </span>
                                </div>
                                <div class="h-10 w-px self-center bg-gray-200"></div>
                                <div class="flex flex-col text-right">
                                    <span class="text-xs font-medium text-gray-500">
                                        {{ t('Open changes') }}
                                    </span>
                                    <span
                                        class="text-lg font-semibold"
                                        :class="pendingChanges > 0 ? 'text-amber-600' : 'text-emerald-600'"
                                    >
                                        {{ pendingChanges }}
                                    </span>
                                </div>
                            </div>

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

                    <!-- Filterleiste -->
                    <div class="border-t border-gray-100 px-4 py-3 sm:px-6">
                        <div class="flex flex-wrap items-center justify-between gap-3">
                            <div class="flex flex-wrap gap-2">
                                <button
                                    v-for="filter in filters"
                                    :key="filter.value"
                                    type="button"
                                    class="inline-flex items-center gap-1.5 rounded-full px-3 py-1 text-xs font-medium transition"
                                    :class="activeFilter === filter.value
                                        ? 'bg-indigo-600 text-white shadow-sm'
                                        : 'bg-gray-100 text-gray-700 hover:bg-gray-200'"
                                    @click="activeFilter = filter.value"
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
                            </div>

                            <p class="text-xs text-gray-400">
                                {{ t('Changes with pending approval are highlighted.') }}
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Desktop: Tabelle -->
                <div class="hidden md:block">
                    <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white/90 shadow-sm backdrop-blur-sm">
                        <table class="min-w-full divide-y divide-gray-100">
                            <thead class="bg-gray-50/80">
                            <tr>
                                <th
                                    scope="col"
                                    class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 sm:px-6"
                                >
                                    {{ t('Affected entity') }}
                                </th>
                                <th
                                    scope="col"
                                    class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 sm:px-6"
                                >
                                    {{ t('Working time before') }}
                                </th>
                                <th
                                    scope="col"
                                    class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 sm:px-6"
                                >
                                    {{ t('Working time after') }}
                                </th>
                                <th
                                    scope="col"
                                    class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 sm:px-6"
                                >
                                    {{ t('Changed by') }}
                                </th>
                                <th
                                    scope="col"
                                    class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 sm:px-6"
                                >
                                    {{ t('Changed at') }}
                                </th>
                                <th
                                    scope="col"
                                    class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 sm:px-6"
                                >
                                    {{ t('Status') }}
                                </th>
                                <th scope="col" class="px-4 py-3 sm:px-6"></th>
                            </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                            <tr
                                v-for="change in filteredChanges"
                                :key="change.id"
                                :class="[
                                    !change.acknowledged ? 'bg-amber-50/60' : 'bg-white',
                                    'transition hover:bg-indigo-50/40'
                                ]"
                            >
                                <!-- Betroffene Entität (Person oder Schicht) -->
                                <td class="px-4 py-3 sm:px-6">
                                    <div class="flex items-center gap-3">
                                        <!-- Avatar (Bild oder Initialen) -->
                                        <div
                                            class="flex h-8 w-8 items-center justify-center overflow-hidden rounded-full bg-gradient-to-br from-indigo-500 to-indigo-600 text-xs font-semibold text-white shadow-sm"
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
                                            <span class="text-sm font-medium text-gray-900">
                                                {{ change.affected_name || t('Affects shift') }}
                                            </span>
                                            <span class="text-xs text-gray-500">
                                                {{ describeChange(change) }}
                                            </span>
                                        </div>
                                    </div>
                                </td>

                                <!-- Arbeitszeit vorher -->
                                <td class="px-4 py-3 text-sm text-gray-700 sm:px-6">
                                    <div class="inline-flex items-center gap-1 rounded-full bg-gray-100 px-2 py-1 text-xs text-gray-700">
                                        <span class="h-1.5 w-1.5 rounded-full bg-gray-400"></span>
                                        <span>{{ change.before_label === 'free' ? $t('Free') : change.before_label }}</span>
                                    </div>
                                </td>

                                <!-- Arbeitszeit nachher -->
                                <td class="px-4 py-3 text-sm text-gray-700 sm:px-6">
                                    <div class="inline-flex items-center gap-1 rounded-full bg-emerald-50 px-2 py-1 text-xs text-emerald-700">
                                        <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>
                                        <span>{{ change.after_label === 'free' ? $t('Free') : change.after_label }}</span>
                                    </div>
                                </td>

                                <!-- Geändert von -->
                                <td class="px-4 py-3 text-sm text-gray-700 sm:px-6">
                                    <div class="flex flex-col">
                                        <span class="text-sm text-gray-900">
                                            {{ change.changed_by_name || '–' }}
                                        </span>
                                        <span class="text-xs text-gray-400">
                                            {{ change.changed_at_formatted || '–' }}
                                        </span>
                                    </div>
                                </td>

                                <!-- Geändert am (nur Datum) -->
                                <td class="px-4 py-3 text-sm text-gray-700 sm:px-6">
                                    <span class="whitespace-nowrap text-sm text-gray-700">
                                        {{ change.changed_at_formatted || '–' }}
                                    </span>
                                </td>

                                <!-- Status -->
                                <td class="px-4 py-3 sm:px-6">
                                    <span
                                        v-if="!change.acknowledged"
                                        class="inline-flex items-center gap-1 rounded-full bg-amber-100 px-2 py-1 text-xs font-medium text-amber-800"
                                    >
                                        <span class="h-1.5 w-1.5 rounded-full bg-amber-500"></span>
                                        {{ t('Changed after commitment') }}
                                    </span>
                                    <span
                                        v-else
                                        class="inline-flex items-center gap-1 rounded-full bg-emerald-100 px-2 py-1 text-xs font-medium text-emerald-800"
                                    >
                                        <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>
                                        {{ t('Approval granted') }}
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
                                    <span v-else class="text-xs text-gray-400">
                                        –
                                    </span>
                                </td>
                            </tr>

                            <tr v-if="filteredChanges.length === 0">
                                <td colspan="7" class="px-4 py-8 text-center text-sm text-gray-500 sm:px-6">
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
                        v-for="change in filteredChanges"
                        :key="change.id"
                        :class="[
                            'rounded-2xl border px-4 py-3 shadow-sm',
                            !change.acknowledged
                                ? 'border-amber-200 bg-amber-50/60'
                                : 'border-gray-200 bg-white'
                        ]"
                    >
                        <div class="flex items-center gap-3">
                            <div
                                class="flex h-9 w-9 items-center justify-center overflow-hidden rounded-full bg-gradient-to-br from-indigo-500 to-indigo-600 text-xs font-semibold text-white shadow-sm"
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
                                    <p class="text-sm font-medium text-gray-900">
                                        {{ change.affected_name || t('Affects shift') }}
                                    </p>
                                    <span
                                        v-if="!change.acknowledged"
                                        class="inline-flex items-center rounded-full bg-amber-100 px-2 py-0.5 text-[10px] font-medium text-amber-800"
                                    >
                                        {{ t('Changed after commitment') }}
                                    </span>
                                    <span
                                        v-else
                                        class="inline-flex items-center rounded-full bg-emerald-100 px-2 py-0.5 text-[10px] font-medium text-emerald-800"
                                    >
                                        {{ t('Approval granted') }}
                                    </span>
                                </div>
                                <p class="mt-0.5 text-xs text-gray-500">
                                    {{ describeChange(change) }}
                                </p>
                            </div>
                        </div>

                        <div class="mt-3 grid grid-cols-1 gap-2 text-xs text-gray-700">
                            <div class="flex items-center justify-between gap-2">
                                <span class="text-[11px] font-medium uppercase tracking-wide text-gray-500">
                                    {{ t('Before') }}
                                </span>
                                <span class="inline-flex items-center gap-1 rounded-full bg-gray-100 px-2 py-1">
                                    <span class="h-1.5 w-1.5 rounded-full bg-gray-400"></span>
                                    <span>{{ change.before_label === 'free' ? $t('Free') : change.before_label }}</span>
                                </span>
                            </div>
                            <div class="flex items-center justify-between gap-2">
                                <span class="text-[11px] font-medium uppercase tracking-wide text-gray-500">
                                    {{ t('After') }}
                                </span>
                                <span class="inline-flex items-center gap-1 rounded-full bg-emerald-50 px-2 py-1 text-emerald-700">
                                    <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>
                                    <span>{{ change.after_label === 'free' ? $t('Free') : change.after_label }}</span>
                                </span>
                            </div>
                        </div>

                        <div class="mt-3 flex items-center justify-between gap-2">
                            <div class="flex flex-col text-xs text-gray-500">
                                <span class="font-medium text-gray-700">
                                    {{ change.changed_by_name || '–' }}
                                </span>
                                <span>
                                    {{ change.changed_at_formatted || '–' }}
                                </span>
                            </div>

                            <button
                                v-if="!change.acknowledged"
                                type="button"
                                class="inline-flex items-center rounded-full bg-indigo-600 px-3 py-1.5 text-[11px] font-medium text-white shadow-sm hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-1"
                                @click="acknowledge(change)"
                            >
                                {{ t('Approve') }}
                            </button>
                        </div>
                    </div>

                    <div
                        v-if="filteredChanges.length === 0"
                        class="rounded-2xl border border-gray-200 bg-white px-4 py-6 text-center text-sm text-gray-500"
                    >
                        {{ t('No changes after commitment have been recorded for this craft yet.') }}
                    </div>
                </div>
            </div>
        </div>

        <!-- Dein bestehendes Modal; API bleibt unverändert -->
        <CraftSelectorModal v-if="showCraftSelector" />
    </AppLayout>
</template>

<script setup>
import { computed, ref } from 'vue';
import { useI18n } from 'vue-i18n';
import { router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import CraftSelectorModal from "@/Pages/ShiftPlanRequests/components/CraftSelectorModal.vue";
import BaseUIButton from "@/Artwork/Buttons/BaseUIButton.vue";

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
    changes: {
        type: Array,
        required: true,
    },
});

const { t } = useI18n();

const showCraftSelector = ref(false);
const activeFilter = ref('all');

const filters = [
    { value: 'all',  label: 'All changes' },
    { value: 'open', label: 'Open changes' },
    { value: 'ack',  label: 'Approval granted' },
];

const totalChanges = computed(() => props.changes.length);
const pendingChanges = computed(() => props.changes.filter(c => !c.acknowledged).length);

const filteredChanges = computed(() => {
    if (activeFilter.value === 'open') {
        return props.changes.filter(c => !c.acknowledged);
    }
    if (activeFilter.value === 'ack') {
        return props.changes.filter(c => c.acknowledged);
    }
    return props.changes;
});

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
