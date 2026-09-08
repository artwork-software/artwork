<template>
    <AppLayout :title="$t('Work time change requests')">

        <div class="container mx-auto max-w-6xl px-4 py-6 space-y-6">
            <WorkTimeTabComponent />

            <!-- Filter: Status (Default alle), Zeitraum (Anfragedatum) — in der URL gehalten -->
            <div class="rounded-lg bg-surface border border-border-subtle shadow-raised p-4 flex flex-wrap items-end gap-3">
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
                <div class="w-40">
                    <BaseInput
                        id="work-time-requests-from"
                        v-model="filterState.date_from"
                        type="date"
                        :label="$t('From')"
                        no-margin-top
                        @change="applyFilters"
                    />
                </div>
                <div class="w-40">
                    <BaseInput
                        id="work-time-requests-to"
                        v-model="filterState.date_to"
                        type="date"
                        :label="$t('To')"
                        no-margin-top
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
                    {{ requests.total ?? 0 }} {{ $t('Requests') }}
                </span>
            </div>

            <div
                v-if="!requestList.length"
                class="rounded-2xl border border-dashed border-border-subtle bg-surface p-8 text-center"
            >
                <p class="text-sm text-text-subtle">
                    {{ hasActiveFilters
                        ? $t('No requests match the current filters.')
                        : $t('You have not submitted any work time change requests yet.') }}
                </p>
                <BaseUIButton
                    v-if="hasActiveFilters"
                    class="mt-4"
                    :label="$t('Reset filters')"
                    is-cancel-button
                    @click="resetFilters"
                />
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div
                    v-for="request in requestList"
                    :key="request.id"
                    class="rounded-lg bg-surface border border-border-subtle w-full shadow-raised p-6 relative"
                >
                    <div v-if="request.status === 'pending'" class="absolute top-4 right-4">
                        <ToolTipComponent
                            direction="left"
                            :tooltip-text="$t('Delete request')"
                            icon="IconTrash"
                            icon-size="h-5 w-5 text-danger"
                            classes-button="p-2 hover:bg-surface-sunken rounded-lg transition-colors"
                            @click="askDelete(request)"
                        />
                    </div>
                    <SingleWorkTimeChangeRequest :request="request" :need-approval="false" />
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

        <!-- Bestätigung statt window.confirm -->
        <ArtworkBaseDeleteModal
            v-if="requestToDelete"
            :title="$t('Delete request')"
            :description="$t('Do you really want to delete this request?')"
            @close="requestToDelete = null"
            @delete="deleteRequest"
        />

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
import WorkTimeTabComponent from "@/Pages/WorkTime/Components/WorkTimeTabComponent.vue";
import SingleWorkTimeChangeRequest from "@/Pages/WorkTime/Components/SingleWorkTimeChangeRequest.vue";
import ArtworkBaseDeleteModal from "@/Artwork/Modals/ArtworkBaseDeleteModal.vue";
import ToolTipComponent from "@/Components/ToolTips/ToolTipComponent.vue";
import NotificationToast from "@/Artwork/Feedback/NotificationToast.vue";
import BaseInput from "@/Artwork/Inputs/BaseInput.vue";
import BaseUIButton from "@/Artwork/Buttons/BaseUIButton.vue";
import SearchableSelect from "@/Artwork/Listbox/SearchableSelect.vue";
import BasePaginator from "@/Components/Paginate/BasePaginator.vue";
import { router } from "@inertiajs/vue3";
import { computed, reactive, ref } from "vue";

const props = defineProps({
    // Laravel-Paginator (data, total, per_page, current_page, links, …)
    requests: {
        type: Object,
        required: true
    },
    filters: {
        type: Object,
        default: () => ({ status: 'all', date_from: null, date_to: null, per_page: 25 }),
    },
})

const requestList = computed(() => props.requests?.data ?? []);

const filterState = reactive({
    status: props.filters?.status ?? 'all',
    date_from: props.filters?.date_from ?? '',
    date_to: props.filters?.date_to ?? '',
});

const statusOptions = [
    { value: 'all', label: 'All statuses' },
    { value: 'pending', label: 'pending' },
    { value: 'approved', label: 'approved' },
    { value: 'rejected', label: 'rejected' },
];

const hasActiveFilters = computed(() =>
    filterState.status !== 'all' || !!filterState.date_from || !!filterState.date_to
);

function filterParams() {
    const params = {};
    if (filterState.status && filterState.status !== 'all') params.status = filterState.status;
    if (filterState.date_from) params.date_from = filterState.date_from;
    if (filterState.date_to) params.date_to = filterState.date_to;
    if (props.filters?.per_page && props.filters.per_page !== 25) params.per_page = props.filters.per_page;
    return params;
}

function visit(extra = {}) {
    router.get(route('work-time-request.index'), { ...filterParams(), ...extra }, {
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
    filterState.date_from = '';
    filterState.date_to = '';
    applyFilters();
}

function goToPage(page) {
    visit({ page });
}

// Erlaubte Seitengrößen: 25/50/100 (kleinere Werte des Paginator-Menüs werden auf 25 gehoben)
function changePerPage(perPage) {
    const allowed = [25, 50, 100].includes(Number(perPage)) ? Number(perPage) : 25;
    router.get(route('work-time-request.index'), { ...filterParams(), per_page: allowed, page: 1 }, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
}

const requestToDelete = ref(null);
const toast = ref(null);
const toastVisible = ref(false);

const askDelete = (request) => {
    requestToDelete.value = request;
};

const deleteRequest = () => {
    const request = requestToDelete.value;
    if (!request) return;
    router.delete(route('worktime.change-request.destroy', { workTimeChangeRequest: request.id }), {
        preserveScroll: true,
        onSuccess: () => {
            requestToDelete.value = null;
            toast.value = { title: 'Request deleted', description: '', type: 'success' };
            toastVisible.value = true;
        },
        onError: (error) => {
            console.error('Error deleting request:', error);
            requestToDelete.value = null;
            toast.value = { title: 'The request could not be deleted.', description: '', type: 'error' };
            toastVisible.value = true;
        }
    });
}
</script>

<style scoped>

</style>
