<template>
    <app-layout :title="$t('Compensation days overview')">
        <div class="artwork-container-fluid">
            <ToolbarHeader
                band
                :icon="IconCalendarOff"
                :title="$t('Compensation days overview')"
                :description="$t('Overview of all compensation days across all users.')"
                :search-enabled="false"
            />

            <!-- Filter: Gewerk, Person, Frist von–bis, Status — in der URL gehalten (Query) -->
            <div class="mt-6 flex flex-wrap items-end gap-3">
                <div class="min-w-[12rem]">
                    <label class="block text-xs font-medium text-text-subtle mb-1">{{ $t('Craft') }}</label>
                    <SearchableSelect
                        v-model="filterState.craft_id"
                        :options="crafts"
                        value-key="id"
                        :label-key="craft => craft.abbreviation ? `${craft.name} (${craft.abbreviation})` : craft.name"
                        :empty-option="{ label: 'All crafts', value: null }"
                        :placeholder="$t('All crafts')"
                        @change="applyFilters"
                    />
                </div>
                <div class="min-w-[12rem]">
                    <label class="block text-xs font-medium text-text-subtle mb-1">{{ $t('Person') }}</label>
                    <SearchableSelect
                        v-model="filterState.user_id"
                        :options="users"
                        value-key="id"
                        :label-key="user => `${user.last_name}, ${user.first_name}`"
                        :empty-option="{ label: 'All persons', value: null }"
                        :placeholder="$t('All persons')"
                        @change="applyFilters"
                    />
                </div>
                <div class="w-40">
                    <BaseInput
                        id="deadline_from"
                        v-model="filterState.deadline_from"
                        type="date"
                        :label="$t('Deadline from')"
                        no-margin-top
                        @change="applyFilters"
                    />
                </div>
                <div class="w-40">
                    <BaseInput
                        id="deadline_to"
                        v-model="filterState.deadline_to"
                        type="date"
                        :label="$t('Deadline until')"
                        no-margin-top
                        @change="applyFilters"
                    />
                </div>
                <div class="min-w-[10rem]">
                    <label class="block text-xs font-medium text-text-subtle mb-1">{{ $t('Status') }}</label>
                    <SearchableSelect
                        v-model="filterState.status"
                        :options="statusOptions"
                        value-key="value"
                        label-key="label"
                        translate-option-labels
                        :empty-option="{ label: 'All statuses', value: null }"
                        :placeholder="$t('All statuses')"
                        @change="applyFilters"
                    />
                </div>
                <BaseUIButton
                    v-if="hasActiveFilters"
                    :label="$t('Reset filters')"
                    is-cancel-button
                    @click="resetFilters"
                />
                <!-- Export: dieselben Rechte wie das Dashboard (can plan shifts) -->
                <a
                    v-if="can('can plan shifts') || hasAdminRole()"
                    :href="exportUrl"
                    class="ml-auto"
                >
                    <BaseUIButton :label="$t('Export as Excel')" :icon="IconFileSpreadsheet" />
                </a>
            </div>

            <!-- Summary cards -->
            <div class="grid grid-cols-3 gap-4 mt-4">
                <div class="rounded-xl border border-accent-200 bg-accent-50/50 px-5 py-4">
                    <div class="text-xs font-medium text-accent-600 uppercase tracking-wide">{{ $t('Total open') }}</div>
                    <div class="mt-1 text-2xl font-bold text-accent-700">{{ stats.open }}</div>
                    <div class="text-xs text-accent-600 mt-0.5">{{ stats.open_value }} {{ $t('Days') }}</div>
                </div>
                <div class="rounded-xl border border-success-border bg-success-surface/50 px-5 py-4">
                    <div class="text-xs font-medium text-success uppercase tracking-wide">{{ $t('Total granted') }}</div>
                    <div class="mt-1 text-2xl font-bold text-success">{{ stats.granted }}</div>
                    <div class="text-xs text-success mt-0.5">{{ stats.granted_value }} {{ $t('Days') }}</div>
                </div>
                <div class="rounded-xl border border-danger-border bg-danger-surface/50 px-5 py-4">
                    <div class="text-xs font-medium text-danger uppercase tracking-wide">{{ $t('Total overdue') }}</div>
                    <div class="mt-1 text-2xl font-bold text-danger">{{ stats.overdue }}</div>
                    <div class="text-xs text-danger mt-0.5">{{ stats.overdue_value }} {{ $t('Days') }}</div>
                </div>
            </div>

            <!-- Overdue table -->
            <section v-if="overdueCompensations.length" class="mt-8">
                <h3 class="text-sm font-semibold text-text-muted mb-3 flex items-center gap-2">
                    <span class="inline-block h-2 w-2 rounded-full bg-danger"></span>
                    {{ $t('Overdue compensation days') }}
                    <span class="text-xs font-normal text-text-subtle">({{ overdueCompensations.length }})</span>
                </h3>
                <CompensationTable
                    :items="overdueCompensations"
                    show-user
                    :show-grant="true"
                    :show-delete="true"
                    overdue-highlight
                    @grant="openGrantModal"
                    @delete="openDeleteModal"
                />
            </section>

            <!-- Open table -->
            <section class="mt-8">
                <h3 class="text-sm font-semibold text-text-muted mb-3 flex items-center gap-2">
                    <span class="inline-block h-2 w-2 rounded-full bg-accent-600"></span>
                    {{ $t('Open compensation days') }}
                    <span class="text-xs font-normal text-text-subtle">({{ openCompensations.length }})</span>
                </h3>
                <CompensationTable
                    v-if="openCompensations.length"
                    :items="openCompensations"
                    show-user
                    :show-grant="true"
                    :show-delete="true"
                    @grant="openGrantModal"
                    @delete="openDeleteModal"
                />
                <div v-else class="text-xs text-text-subtle italic py-3">{{ $t('No open compensation days.') }}</div>
            </section>

            <!-- Granted table -->
            <section class="mt-8">
                <h3 class="text-sm font-semibold text-text-muted mb-3 flex items-center gap-2">
                    <span class="inline-block h-2 w-2 rounded-full bg-success"></span>
                    {{ $t('Granted compensation days') }}
                    <span class="text-xs font-normal text-text-subtle">({{ grantedCompensations.length }})</span>
                </h3>
                <CompensationTable
                    v-if="grantedCompensations.length"
                    :items="grantedCompensations"
                    show-user
                    show-granted-info
                    :show-revoke="true"
                    :show-delete="true"
                    @revoke="revokeCompensationDay"
                    @delete="openDeleteModal"
                />
                <div v-else class="text-xs text-text-subtle italic py-3">{{ $t('No granted compensation days.') }}</div>
            </section>

            <!-- Recent activity (paginated) -->
            <section v-if="recentActivity.data.length" class="mt-8">
                <h3 class="text-sm font-semibold text-text-muted mb-3 flex items-center gap-2">
                    <span class="inline-block h-2 w-2 rounded-full bg-border-strong"></span>
                    {{ $t('Recent activity') }}
                    <span class="text-xs font-normal text-text-subtle">({{ recentActivity.total }})</span>
                </h3>
                <div class="overflow-hidden rounded-lg border border-border-subtle">
                    <table class="min-w-full text-xs">
                        <thead class="bg-surface-sunken">
                            <tr>
                                <th class="px-3 py-2 text-left font-medium text-text-subtle">{{ $t('Date') }}</th>
                                <th class="px-3 py-2 text-left font-medium text-text-subtle">{{ $t('User') }}</th>
                                <th class="px-3 py-2 text-left font-medium text-text-subtle">{{ $t('Action') }}</th>
                                <th class="px-3 py-2 text-left font-medium text-text-subtle">{{ $t('Details') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-border-subtle">
                            <tr v-for="activity in recentActivity.data" :key="activity.id" class="hover:bg-surface-sunken">
                                <td class="px-3 py-2 text-text-subtle whitespace-nowrap">{{ formatDateTime(activity.created_at) }}</td>
                                <td class="px-3 py-2 text-text-muted whitespace-nowrap">
                                    <template v-if="activity.causer">
                                        {{ activity.causer.first_name }} {{ activity.causer.last_name }}
                                    </template>
                                    <template v-else>-</template>
                                </td>
                                <td class="px-3 py-2">
                                    <span
                                        class="inline-flex px-1.5 py-0.5 text-[10px] font-medium rounded-full"
                                        :class="activityBadgeClass(activity.event)"
                                    >
                                        {{ activityLabel(activity) }}
                                    </span>
                                </td>
                                <td class="px-3 py-2 text-text-muted max-w-[400px]">
                                    <template v-if="activity.properties?.delete_reason">
                                        {{ activity.properties.delete_reason }}
                                    </template>
                                    <template v-else-if="activity.properties?.ignore_reason">
                                        {{ activity.properties.ignore_reason }}
                                    </template>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div v-if="recentActivity.last_page > 1" class="mt-3 flex items-center justify-between">
                    <div class="text-[11px] text-text-subtle">
                        {{ $t('Page') }} {{ recentActivity.current_page }} / {{ recentActivity.last_page }}
                    </div>
                    <div class="flex items-center gap-1.5">
                        <button
                            v-for="link in paginationLinks"
                            :key="link.label"
                            class="rounded px-2.5 py-1 text-[11px] font-medium transition-colors"
                            :class="link.active ? 'bg-accent-700 text-white'
                                : link.url
                                    ? 'bg-surface-sunken text-text-muted hover:bg-border-subtle'
                                    : 'bg-surface-sunken text-text-subtle cursor-not-allowed'"
                            :disabled="!link.url"
                            @click="goToPage(link.url)"
                            v-html="link.label"
                        />
                    </div>
                </div>
            </section>
        </div>

        <GrantCompensationDayModal
            v-if="showGrantModal && grantUserId"
            :user-id="grantUserId"
            :user-name="grantUserName"
            @close="showGrantModal = false"
            @granted="handleGranted"
        />

        <DeleteCompensationDayModal
            v-if="showDeleteModal && selectedCompDayToDelete"
            :compensation-day="selectedCompDayToDelete"
            @close="showDeleteModal = false; selectedCompDayToDelete = null"
            @deleted="handleDeleted"
        />
    </app-layout>
</template>

<script setup>
import { ref, reactive, computed } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import AppLayout from '@/Layouts/AppLayout.vue';
import ToolbarHeader from '@/Artwork/Toolbar/ToolbarHeader.vue';
import SearchableSelect from '@/Artwork/Listbox/SearchableSelect.vue';
import BaseInput from '@/Artwork/Inputs/BaseInput.vue';
import BaseUIButton from '@/Artwork/Buttons/BaseUIButton.vue';
import GrantCompensationDayModal from '@/Pages/Shifts/Components/GrantCompensationDayModal.vue';
import DeleteCompensationDayModal from '@/Pages/Shifts/Components/DeleteCompensationDayModal.vue';
import CompensationTable from '@/Pages/CompensationDays/CompensationTable.vue';
import { usePermission } from '@/Composeables/Permission.js';
import { IconCalendarOff, IconFileSpreadsheet } from '@tabler/icons-vue';

const { t } = useI18n();
const { can, hasAdminRole } = usePermission(usePage().props);

const props = defineProps({
    openCompensations: { type: Array, default: () => [] },
    grantedCompensations: { type: Array, default: () => [] },
    overdueCompensations: { type: Array, default: () => [] },
    stats: { type: Object, default: () => ({}) },
    recentActivity: { type: Object, default: () => ({ data: [], total: 0, current_page: 1, last_page: 1, links: [] }) },
    crafts: { type: Array, default: () => [] },
    users: { type: Array, default: () => [] },
    selectedCraftId: { type: Number, default: null },
    filters: { type: Object, default: () => ({}) },
});

// Filterzustand aus der URL (Backend gibt die validierten Filter zurück)
const filterState = reactive({
    craft_id: props.filters?.craft_id ?? props.selectedCraftId ?? null,
    user_id: props.filters?.user_id ?? null,
    deadline_from: props.filters?.deadline_from ?? '',
    deadline_to: props.filters?.deadline_to ?? '',
    status: props.filters?.status ?? null,
});

const statusOptions = [
    { value: 'open', label: 'Open' },
    { value: 'granted', label: 'Granted' },
    { value: 'overdue', label: 'Overdue' },
];

const hasActiveFilters = computed(() =>
    !!(filterState.craft_id || filterState.user_id || filterState.deadline_from || filterState.deadline_to || filterState.status)
);

function filterParams() {
    const params = {};
    if (filterState.craft_id) params.craft_id = filterState.craft_id;
    if (filterState.user_id) params.user_id = filterState.user_id;
    if (filterState.deadline_from) params.deadline_from = filterState.deadline_from;
    if (filterState.deadline_to) params.deadline_to = filterState.deadline_to;
    if (filterState.status) params.status = filterState.status;
    return params;
}

function applyFilters() {
    router.get(route('compensation-day-offs.dashboard'), filterParams(), {
        preserveState: true,
        preserveScroll: true,
    });
}

function resetFilters() {
    filterState.craft_id = null;
    filterState.user_id = null;
    filterState.deadline_from = '';
    filterState.deadline_to = '';
    filterState.status = null;
    applyFilters();
}

const exportUrl = computed(() => route('compensation-day-offs.export', filterParams()));

const showGrantModal = ref(false);
const grantUserId = ref(null);
const grantUserName = ref('');
const showDeleteModal = ref(false);
const selectedCompDayToDelete = ref(null);

const paginationLinks = computed(() => {
    return (props.recentActivity.links || []).filter(link => {
        // Filter out "..." links without URL for cleaner pagination
        return link.url || link.active;
    });
});

function goToPage(url) {
    if (!url) return;
    // Filter beim Blättern beibehalten
    const pageUrl = new URL(url);
    Object.entries(filterParams()).forEach(([key, value]) => pageUrl.searchParams.set(key, value));
    router.get(pageUrl.toString(), {}, {
        preserveState: true,
        preserveScroll: true,
    });
}

function formatDateTime(dt) {
    if (!dt) return '-';
    const d = new Date(dt);
    return d.toLocaleDateString('de-DE') + ' ' + d.toLocaleTimeString('de-DE', { hour: '2-digit', minute: '2-digit' });
}

function activityBadgeClass(event) {
    if (event === 'deleted_with_reason' || event === 'deleted') return 'bg-danger-surface text-danger';
    if (event === 'updated') return 'bg-accent-100 text-accent-700';
    if (event === 'created') return 'bg-success-surface text-success';
    return 'bg-surface-sunken text-text-muted';
}

function activityLabel(activity) {
    const logName = activity.log_name;
    const event = activity.event;

    if (logName === 'compensation_day_off') {
        if (event === 'deleted_with_reason' || event === 'deleted') return t('Compensation day deleted');
        if (event === 'created') return t('Compensation day created');
        if (event === 'updated') return t('Compensation day updated');
    }
    if (logName === 'shift_rule_violation') {
        if (event === 'updated') return t('Violation updated');
        if (event === 'created') return t('Violation created');
    }
    return event || logName;
}

function openGrantModal(item) {
    grantUserId.value = item.user_id;
    grantUserName.value = item.user ? `${item.user.first_name} ${item.user.last_name}` : '';
    showGrantModal.value = true;
}

function openDeleteModal(item) {
    selectedCompDayToDelete.value = item;
    showDeleteModal.value = true;
}

function revokeCompensationDay(item) {
    router.post(route('compensation-day-offs.revoke', { compensationDayOff: item.id }), {}, {
        preserveScroll: true,
        onSuccess: () => router.reload(),
    });
}

function handleGranted() {
    showGrantModal.value = false;
    router.reload();
}

function handleDeleted() {
    showDeleteModal.value = false;
    selectedCompDayToDelete.value = null;
    router.reload();
}
</script>
