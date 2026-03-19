<template>
    <app-layout :title="$t('Compensation days overview')">
        <div class="artwork-container">
            <ToolbarHeader
                :icon="IconCalendarOff"
                :title="$t('Compensation days overview')"
                icon-bg-class="bg-teal-600/10 text-teal-700"
                :description="$t('Overview of all compensation days across all users.')"
                :search-enabled="false"
            />

            <!-- Craft filter -->
            <div class="mt-6 flex items-center gap-3">
                <label class="text-xs font-medium text-zinc-500">{{ $t('Craft') }}:</label>
                <select
                    v-model="selectedCraft"
                    class="rounded-md border border-zinc-300 bg-white px-3 py-1.5 text-xs focus:border-blue-400 focus:ring-1 focus:ring-blue-400"
                    @change="onCraftChange"
                >
                    <option :value="null">{{ $t('All crafts') }}</option>
                    <option v-for="craft in crafts" :key="craft.id" :value="craft.id">
                        {{ craft.name }}
                        <template v-if="craft.abbreviation"> ({{ craft.abbreviation }})</template>
                    </option>
                </select>
            </div>

            <!-- Summary cards -->
            <div class="grid grid-cols-3 gap-4 mt-4">
                <div class="rounded-xl border border-blue-200 bg-blue-50/50 px-5 py-4">
                    <div class="text-xs font-medium text-blue-600 uppercase tracking-wide">{{ $t('Total open') }}</div>
                    <div class="mt-1 text-2xl font-bold text-blue-900">{{ stats.open }}</div>
                    <div class="text-xs text-blue-500 mt-0.5">{{ stats.open_value }} {{ $t('Days') }}</div>
                </div>
                <div class="rounded-xl border border-emerald-200 bg-emerald-50/50 px-5 py-4">
                    <div class="text-xs font-medium text-emerald-600 uppercase tracking-wide">{{ $t('Total granted') }}</div>
                    <div class="mt-1 text-2xl font-bold text-emerald-900">{{ stats.granted }}</div>
                    <div class="text-xs text-emerald-500 mt-0.5">{{ stats.granted_value }} {{ $t('Days') }}</div>
                </div>
                <div class="rounded-xl border border-red-200 bg-red-50/50 px-5 py-4">
                    <div class="text-xs font-medium text-red-600 uppercase tracking-wide">{{ $t('Total overdue') }}</div>
                    <div class="mt-1 text-2xl font-bold text-red-900">{{ stats.overdue }}</div>
                    <div class="text-xs text-red-500 mt-0.5">{{ stats.overdue_value }} {{ $t('Days') }}</div>
                </div>
            </div>

            <!-- Overdue table -->
            <section v-if="overdueCompensations.length" class="mt-8">
                <h3 class="text-sm font-semibold text-zinc-700 mb-3 flex items-center gap-2">
                    <span class="inline-block h-2 w-2 rounded-full bg-red-500"></span>
                    {{ $t('Overdue compensation days') }}
                    <span class="text-xs font-normal text-zinc-400">({{ overdueCompensations.length }})</span>
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
                <h3 class="text-sm font-semibold text-zinc-700 mb-3 flex items-center gap-2">
                    <span class="inline-block h-2 w-2 rounded-full bg-blue-500"></span>
                    {{ $t('Open compensation days') }}
                    <span class="text-xs font-normal text-zinc-400">({{ openCompensations.length }})</span>
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
                <div v-else class="text-xs text-zinc-500 italic py-3">{{ $t('No open compensation days.') }}</div>
            </section>

            <!-- Granted table -->
            <section class="mt-8">
                <h3 class="text-sm font-semibold text-zinc-700 mb-3 flex items-center gap-2">
                    <span class="inline-block h-2 w-2 rounded-full bg-emerald-500"></span>
                    {{ $t('Granted compensation days') }}
                    <span class="text-xs font-normal text-zinc-400">({{ grantedCompensations.length }})</span>
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
                <div v-else class="text-xs text-zinc-500 italic py-3">{{ $t('No granted compensation days.') }}</div>
            </section>

            <!-- Recent activity (paginated) -->
            <section v-if="recentActivity.data.length" class="mt-8">
                <h3 class="text-sm font-semibold text-zinc-700 mb-3 flex items-center gap-2">
                    <span class="inline-block h-2 w-2 rounded-full bg-zinc-400"></span>
                    {{ $t('Recent activity') }}
                    <span class="text-xs font-normal text-zinc-400">({{ recentActivity.total }})</span>
                </h3>
                <div class="overflow-hidden rounded-lg border border-zinc-200">
                    <table class="min-w-full text-xs">
                        <thead class="bg-zinc-50">
                            <tr>
                                <th class="px-3 py-2 text-left font-medium text-zinc-500">{{ $t('Date') }}</th>
                                <th class="px-3 py-2 text-left font-medium text-zinc-500">{{ $t('User') }}</th>
                                <th class="px-3 py-2 text-left font-medium text-zinc-500">{{ $t('Action') }}</th>
                                <th class="px-3 py-2 text-left font-medium text-zinc-500">{{ $t('Details') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-zinc-100">
                            <tr v-for="activity in recentActivity.data" :key="activity.id" class="hover:bg-zinc-50/50">
                                <td class="px-3 py-2 text-zinc-500 whitespace-nowrap">{{ formatDateTime(activity.created_at) }}</td>
                                <td class="px-3 py-2 text-zinc-700 whitespace-nowrap">
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
                                <td class="px-3 py-2 text-zinc-600 max-w-[400px]">
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
                    <div class="text-[11px] text-zinc-400">
                        {{ $t('Page') }} {{ recentActivity.current_page }} / {{ recentActivity.last_page }}
                    </div>
                    <div class="flex items-center gap-1.5">
                        <button
                            v-for="link in paginationLinks"
                            :key="link.label"
                            class="rounded px-2.5 py-1 text-[11px] font-medium transition-colors"
                            :class="link.active
                                ? 'bg-artwork-buttons-hover text-white'
                                : link.url
                                    ? 'bg-zinc-100 text-zinc-600 hover:bg-zinc-200'
                                    : 'bg-zinc-50 text-zinc-300 cursor-not-allowed'"
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
import { ref, computed } from 'vue';
import { router } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import AppLayout from '@/Layouts/AppLayout.vue';
import ToolbarHeader from '@/Artwork/Toolbar/ToolbarHeader.vue';
import GrantCompensationDayModal from '@/Pages/Shifts/Components/GrantCompensationDayModal.vue';
import DeleteCompensationDayModal from '@/Pages/Shifts/Components/DeleteCompensationDayModal.vue';
import CompensationTable from '@/Pages/CompensationDays/CompensationTable.vue';
import { IconCalendarOff } from '@tabler/icons-vue';

const { t } = useI18n();

const props = defineProps({
    openCompensations: { type: Array, default: () => [] },
    grantedCompensations: { type: Array, default: () => [] },
    overdueCompensations: { type: Array, default: () => [] },
    stats: { type: Object, default: () => ({}) },
    recentActivity: { type: Object, default: () => ({ data: [], total: 0, current_page: 1, last_page: 1, links: [] }) },
    crafts: { type: Array, default: () => [] },
    selectedCraftId: { type: Number, default: null },
});

const selectedCraft = ref(props.selectedCraftId);
const showGrantModal = ref(false);
const grantUserId = ref(null);
const grantUserName = ref('');
const showDeleteModal = ref(false);
const selectedCompDayToDelete = ref(null);

function onCraftChange() {
    const params = {};
    if (selectedCraft.value) {
        params.craft_id = selectedCraft.value;
    }
    router.get(route('compensation-day-offs.dashboard'), params, {
        preserveState: true,
        preserveScroll: true,
    });
}

const paginationLinks = computed(() => {
    return (props.recentActivity.links || []).filter(link => {
        // Filter out "..." links without URL for cleaner pagination
        return link.url || link.active;
    });
});

function goToPage(url) {
    if (!url) return;
    // Preserve craft_id when paginating
    const pageUrl = new URL(url);
    if (selectedCraft.value) {
        pageUrl.searchParams.set('craft_id', selectedCraft.value);
    }
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
    if (event === 'deleted_with_reason' || event === 'deleted') return 'bg-red-100 text-red-700';
    if (event === 'updated') return 'bg-blue-100 text-blue-700';
    if (event === 'created') return 'bg-green-100 text-green-700';
    return 'bg-zinc-100 text-zinc-700';
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
