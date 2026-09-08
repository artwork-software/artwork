<template>
    <AppLayout :title="isPlanner ? $t('Requested duty rosters') : $t('My approval requests')">
        <div class="px-4 py-6 sm:px-6 lg:px-8 max-w-7xl mx-auto space-y-6">
            <!-- Header -->
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h1 class="text-xl font-semibold text-text">
                        {{ isPlanner ? $t('Shift plan requests') : $t('My approval requests') }}
                    </h1>
                    <p class="mt-1 text-sm text-text-subtle max-w-2xl">
                        {{ isPlanner
                            ? $t('Here you can see all shift plan requests, newest calendar week first.')
                            : $t('Here you can see all duty rosters you requested, newest calendar week first.')
                        }}
                    </p>
                </div>
                <ShiftPlanWeekShortcut />
            </div>

            <!-- Filter: Status (Default alle), Gewerk, nur eigene Anfragen — in der URL gehalten -->
            <div class="rounded-2xl border border-border-subtle bg-white shadow-sm p-4 flex flex-wrap items-end gap-3">
                <div class="min-w-[11rem]">
                    <SearchableSelect
                        v-model="filterState.status"
                        :options="statusOptions"
                        value-key="value"
                        label-key="label"
                        translate-option-labels
                        :label="$t('Status')"
                        @change="applyFilters"
                    />
                </div>
                <div class="min-w-[13rem]">
                    <SearchableSelect
                        v-model="filterState.craft_id"
                        :options="crafts"
                        value-key="id"
                        :label-key="craft => craft.abbreviation ? `${craft.name} (${craft.abbreviation})` : craft.name"
                        :empty-option="{ label: 'All crafts', value: null }"
                        :placeholder="$t('All crafts')"
                        :label="$t('Craft')"
                        @change="applyFilters"
                    />
                </div>
                <div v-if="isPlanner" class="pb-1.5">
                    <BaseCheckbox
                        id="shift-plan-requests-only-mine"
                        v-model="filterState.only_mine"
                        :label="$t('Only my requests')"
                        @change="applyFilters"
                    />
                </div>
                <BaseUIButton
                    v-if="hasActiveFilters"
                    :label="$t('Reset filters')"
                    is-cancel-button
                    @click="resetFilters"
                />
                <span class="ml-auto text-xs text-text-subtle tabular-nums">
                    {{ $t('Requests') }}: {{ requests.total ?? 0 }}
                </span>
            </div>

            <!-- Keine Requests -->
            <div
                v-if="!rows.length"
                class="rounded-2xl border border-dashed border-border-subtle bg-white p-8 text-center"
            >
                <p class="text-sm text-text-subtle">
                    {{ hasActiveFilters ? $t('No requests match the current filters.') : $t('You have no approval requests.') }}
                </p>
                <BaseUIButton
                    v-if="hasActiveFilters"
                    class="mt-4"
                    :label="$t('Reset filters')"
                    is-cancel-button
                    @click="resetFilters"
                />
            </div>

            <!-- Flache Liste, KW absteigend, serverseitig paginiert -->
            <div v-else class="rounded-2xl border border-border-subtle bg-white shadow-sm divide-y divide-border-subtle">
                <div
                    v-for="request in rows"
                    :key="request.id"
                    class="group w-full text-left px-4 py-3 flex items-center justify-between gap-3 hover:bg-accent-50/60 transition cursor-pointer"
                    role="button"
                    tabindex="0"
                    @click="goToRequest(request.id)"
                    @keydown.enter.prevent="goToRequest(request.id)"
                >
                    <div class="flex items-center gap-3 min-w-0">
                        <div
                            class="h-9 w-9 shrink-0 rounded-full flex items-center justify-center text-xs font-semibold text-white shadow-sm"
                            :style="{ backgroundColor: request.craft?.color || '#4f46e5' }"
                            :title="request.craft?.name"
                        >
                            {{ request.craft?.abbreviation }}
                        </div>
                        <div class="flex flex-col min-w-0">
                            <div class="flex items-center gap-2 flex-wrap">
                                <span class="text-sm font-medium text-text">
                                    {{ $t('KW') }} {{ request.week_number }} / {{ request.year }}
                                </span>
                                <span class="text-xs text-text-subtle truncate">{{ request.craft?.name }}</span>
                                <span
                                    :class="[ 'inline-flex items-center rounded-full px-2 py-0.5 text-[11px] font-medium ring-1',
                                        statusClasses(request.status)
                                    ]"
                                >
                                    <span class="w-1.5 h-1.5 rounded-full mr-1.5 bg-current"></span>
                                    {{ statusLabel(request.status) }}
                                </span>
                            </div>
                            <p class="mt-0.5 text-xs text-text-subtle">
                                {{ $t('Requested on') }}: {{ formatDateTime(request.requested_at) }}
                                <template v-if="isPlanner && request.requested_by_name">
                                    · {{ $t('Requested by') }}: {{ request.requested_by_name }}
                                </template>
                            </p>
                        </div>
                    </div>

                    <div class="flex items-center gap-2 shrink-0">
                        <!-- Antragsteller*in: zurückziehen (pending) / erneut einreichen (rejected) -->
                        <ToolTipComponent
                            v-if="canWithdraw(request, !isPlanner)"
                            direction="left"
                            :tooltip-text="$t('Withdraw request')"
                            icon="IconArrowBackUp"
                            icon-size="h-4 w-4 text-text-muted"
                            classes-button="p-1.5 rounded-lg hover:bg-surface-sunken transition"
                            @click.stop="askWithdraw(request)"
                        />
                        <ToolTipComponent
                            v-if="canResubmit(request, !isPlanner) && !request.has_pending_sibling"
                            direction="left"
                            :tooltip-text="$t('Resubmit for approval')"
                            icon="IconSend"
                            icon-size="h-4 w-4 text-text-muted"
                            classes-button="p-1.5 rounded-lg hover:bg-surface-sunken transition"
                            @click.stop="resubmit(request, request.craft_id)"
                        />
                        <span class="text-xs text-text-subtle">
                            {{ $t('Details') }}
                        </span>
                        <IconChevronRight class="h-4 w-4 text-text-subtle group-hover:text-accent-600" />
                    </div>
                </div>
            </div>

            <BasePaginator
                v-if="requests.total > 0"
                :entities="requests"
                property-name="requests"
                emit-update-entities-per-page
                @update-page="goToPage"
                @update-entities-per-page="changePerPage"
            />
        </div>

        <!-- Zurückziehen bestätigen -->
        <ArtworkBaseModal
            v-if="withdrawTarget"
            :title="$t('Withdraw request')"
            :description="withdrawDescription"
            @close="withdrawTarget = null"
        >
            <div class="space-y-4">
                <BaseAlertComponent
                    type="warning"
                    use-translation
                    message="The shifts of this request are released again and can be resubmitted later."
                />
                <div class="flex items-center justify-between">
                    <BaseUIButton type="button" is-cancel-button :label="$t('Cancel')" @click="withdrawTarget = null" />
                    <BaseUIButton
                        type="button"
                        is-delete-button
                        icon="IconArrowBackUp"
                        :label="$t('Withdraw request')"
                        :processing="processing === 'withdraw'"
                        @click="confirmWithdraw"
                    />
                </div>
            </div>
        </ArtworkBaseModal>

        <NotificationToast
            v-if="toast"
            v-model:show="toastVisible"
            :title="toast.title"
            :description="toast.description"
            :type="toast.type"
        />
    </AppLayout>
</template>

<script setup>
import AppLayout from "@/Layouts/AppLayout.vue";
import ShiftPlanWeekShortcut from "@/Pages/ShiftPlanRequests/components/ShiftPlanWeekShortcut.vue";
import ToolTipComponent from "@/Components/ToolTips/ToolTipComponent.vue";
import ArtworkBaseModal from "@/Artwork/Modals/ArtworkBaseModal.vue";
import BaseAlertComponent from "@/Components/Alerts/BaseAlertComponent.vue";
import BaseUIButton from "@/Artwork/Buttons/BaseUIButton.vue";
import BaseCheckbox from "@/Artwork/Inputs/BaseCheckbox.vue";
import SearchableSelect from "@/Artwork/Listbox/SearchableSelect.vue";
import BasePaginator from "@/Components/Paginate/BasePaginator.vue";
import NotificationToast from "@/Artwork/Feedback/NotificationToast.vue";
import { router } from "@inertiajs/vue3";
import { computed, reactive, ref } from "vue";
import {
    IconChevronRight,
} from "@tabler/icons-vue";
import { useShiftPlanRequest } from "@/Pages/ShiftPlanRequests/components/useShiftPlanRequest.js";
import { useShiftPlanRequestActions } from "@/Pages/ShiftPlanRequests/components/useShiftPlanRequestActions.js";

const props = defineProps({
    // Laravel-Paginator (data, total, per_page, current_page, links, …), KW absteigend
    requests: { type: Object, required: true },
    crafts: { type: Array, default: () => [] },
    filters: { type: Object, default: () => ({ status: 'all', craft_id: null, only_mine: false }) },
    isPlanner: { type: Boolean, default: false },
});

const rows = computed(() => props.requests?.data ?? []);

// Status-Label/-Klassen zentral (Keys pending/approved/rejected, Design-Tokens)
const { statusLabel, statusClasses } = useShiftPlanRequest();

const {
    canWithdraw,
    canResubmit,
    weekLabel,
    toast,
    toastVisible,
    processing,
    withdraw,
    resubmit,
} = useShiftPlanRequestActions();

const filterState = reactive({
    status: props.filters?.status ?? 'all',
    craft_id: props.filters?.craft_id ?? null,
    only_mine: !!props.filters?.only_mine,
});

const statusOptions = [
    { value: 'all', label: 'All statuses' },
    { value: 'pending', label: 'pending' },
    { value: 'approved', label: 'approved' },
    { value: 'rejected', label: 'rejected' },
];

const hasActiveFilters = computed(() =>
    filterState.status !== 'all' || !!filterState.craft_id || filterState.only_mine
);

function filterParams() {
    const params = {};
    if (filterState.status && filterState.status !== 'all') params.status = filterState.status;
    if (filterState.craft_id) params.craft_id = filterState.craft_id;
    if (filterState.only_mine) params.only_mine = 1;
    if (props.requests?.per_page && Number(props.requests.per_page) !== 25) params.per_page = props.requests.per_page;
    return params;
}

function visit(extra = {}) {
    router.get(route('shift-plan-requests.my.index'), { ...filterParams(), ...extra }, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
}

function applyFilters() {
    visit({ page: 1 });
}

function resetFilters() {
    filterState.status = 'all';
    filterState.craft_id = null;
    filterState.only_mine = false;
    applyFilters();
}

function goToPage(page) {
    visit({ page });
}

// Erlaubte Seitengrößen: 25/50/100 (kleinere Werte des Paginator-Menüs werden auf 25 gehoben)
function changePerPage(perPage) {
    const allowed = [25, 50, 100].includes(Number(perPage)) ? Number(perPage) : 25;
    router.get(route('shift-plan-requests.my.index'), { ...filterParams(), per_page: allowed, page: 1 }, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
}

const withdrawTarget = ref(null);
const withdrawDescription = computed(() => {
    if (!withdrawTarget.value) return '';
    const request = withdrawTarget.value;
    return `${request.craft?.name ?? ''} · ${weekLabel(request)}`;
});

const askWithdraw = (request) => {
    withdrawTarget.value = request;
};

const confirmWithdraw = () => {
    if (!withdrawTarget.value) return;
    withdraw(withdrawTarget.value, {
        onSuccess: () => {
            withdrawTarget.value = null;
        },
    });
};

const formatDateTime = (value) => {
    if (!value) return "-";
    const date = new Date(value);
    return date.toLocaleString(undefined, {
        day: "2-digit",
        month: "2-digit",
        year: "numeric",
        hour: "2-digit",
        minute: "2-digit",
    });
};

const goToRequest = (id) => {
    router.get(route("shift-plan-requests.my.show", id));
};
</script>
