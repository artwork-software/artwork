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
                            ? $t('Here you can see all shift plan requests grouped by craft.')
                            : $t('Here you can see all duty rosters you requested, grouped by craft.')
                        }}
                    </p>
                </div>
                <ShiftPlanWeekShortcut />
            </div>

            <!-- Kein Craft / keine Requests -->
            <div
                v-if="!crafts || !crafts.length"
                class="rounded-2xl border border-dashed border-border-subtle bg-white p-8 text-center"
            >
                <p class="text-sm text-text-subtle">
                    {{ $t('You have no approval requests.') }}
                </p>
            </div>

            <!-- Cards pro Craft -->
            <div class="grid gap-6 lg:grid-cols-2 xl:grid-cols-3">
                <div
                    v-for="craft in crafts"
                    :key="craft.id"
                    class="group flex flex-col rounded-2xl border border-border-subtle bg-white shadow-sm hover:border-accent-200 hover:shadow-md transition"
                >
                    <!-- Craft Header -->
                    <div
                        class="flex items-center justify-between px-4 py-3 border-b border-border-subtle bg-gradient-to-r from-white to-surface-sunken rounded-t-2xl"
                    >
                        <div class="flex items-center gap-3">
                            <div
                                class="h-9 w-9 rounded-full flex items-center justify-center text-xs font-semibold text-white shadow-sm"
                                :style="{ backgroundColor: craft.color || '#4f46e5' }"
                            >
                                {{ craft.abbreviation }}
                            </div>
                            <div>
                                <h2 class="text-sm font-semibold text-text">
                                    {{ craft.name }}
                                </h2>
                                <p class="text-xs text-text-subtle">
                                    <span v-if="craft.assignable_by_all">
                                        {{ $t('Assignable by all planners') }}
                                    </span>
                                    <span v-else>
                                        {{ $t('Restricted assignment') }}
                                    </span>
                                </p>
                            </div>
                        </div>

                        <div class="flex flex-col items-end gap-1">
                            <span
                                class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium bg-surface-sunken text-text-muted"
                            >
                                {{ $t('Requests') }}: {{ craft.shift_plan_requests.length }}
                            </span>
                        </div>
                    </div>

                    <!-- Requests-Liste -->
                    <div class="flex-1">
                        <div v-if="craft.shift_plan_requests.length" class="divide-y divide-border-subtle">
                            <div
                                v-for="request in craft.shift_plan_requests"
                                :key="request.id"
                                class="w-full text-left px-4 py-3 flex items-center justify-between gap-3 hover:bg-accent-50/60 transition cursor-pointer"
                                role="button"
                                tabindex="0"
                                @click="goToRequest(request.id)"
                                @keydown.enter.prevent="goToRequest(request.id)"
                            >
                                <div class="flex items-center gap-3 min-w-0">
                                    <div class="flex flex-col min-w-0">
                                        <div class="flex items-center gap-2">
                                            <span class="text-sm font-medium text-text">
                                                {{ $t('KW') }} {{ request.week_number }} / {{ request.year }}
                                            </span>
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
                                            {{ $t('Requested on') }}:
                                            {{ formatDateTime(request.requested_at) }}
                                        </p>
                                        <p v-if="isPlanner && request.requested_by_name" class="text-xs text-text-subtle">
                                            {{ $t('Requested by') }}: {{ request.requested_by_name }}
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
                                        @click.stop="askWithdraw(request, craft)"
                                    />
                                    <ToolTipComponent
                                        v-if="canResubmit(request, !isPlanner) && !hasPendingSibling(request, craft)"
                                        direction="left"
                                        :tooltip-text="$t('Resubmit for approval')"
                                        icon="IconSend"
                                        icon-size="h-4 w-4 text-text-muted"
                                        classes-button="p-1.5 rounded-lg hover:bg-surface-sunken transition"
                                        @click.stop="resubmit(request, craft.id)"
                                    />
                                    <span class="text-xs text-text-subtle">
                                        {{ $t('Details') }}
                                    </span>
                                    <IconChevronRight class="h-4 w-4 text-text-subtle group-hover:text-accent-600" />
                                </div>
                            </div>
                        </div>

                        <!-- Keine Requests für dieses Craft -->
                        <div
                            v-else
                            class="px-4 py-6 text-center text-xs text-text-subtle"
                        >
                            {{ $t('No approval requests for this craft.') }}
                        </div>
                    </div>
                </div>
            </div>
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
import NotificationToast from "@/Artwork/Feedback/NotificationToast.vue";
import { router } from "@inertiajs/vue3";
import { computed, ref } from "vue";
import {
    IconChevronRight,
} from "@tabler/icons-vue";
import { useShiftPlanRequest } from "@/Pages/ShiftPlanRequests/components/useShiftPlanRequest.js";
import { useShiftPlanRequestActions } from "@/Pages/ShiftPlanRequests/components/useShiftPlanRequestActions.js";

const props = defineProps({
    crafts: { type: Array, required: true },
    isPlanner: { type: Boolean, default: false },
});

// Status-Label/-Klassen zentral (Keys pending/approved/rejected, Design-Tokens)
const { statusLabel, statusClasses } = useShiftPlanRequest();

// Antragsteller*in-Aktionen. Der Listen-Payload trägt (noch) kein requested_by_user_id:
// ohne Planer-Sicht (isPlanner=false) sind alle Einträge eigene Anfragen (Controller-Filter).
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

const withdrawTarget = ref(null);
const withdrawDescription = computed(() => {
    if (!withdrawTarget.value) return '';
    const { request, craft } = withdrawTarget.value;
    return `${craft?.name ?? ''} · ${weekLabel(request)}`;
});

// Nach erneutem Einreichen existiert für Gewerk/KW schon eine ausstehende Anfrage → kein zweiter Re-Submit
const hasPendingSibling = (request, craft) => (craft?.shift_plan_requests ?? []).some(
    (other) => other.id !== request.id
        && other.status === 'pending'
        && Number(other.week_number) === Number(request.week_number)
        && Number(other.year) === Number(request.year)
);

const askWithdraw = (request, craft) => {
    withdrawTarget.value = { request, craft };
};

const confirmWithdraw = () => {
    if (!withdrawTarget.value) return;
    withdraw(withdrawTarget.value.request, {
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
