<template>
    <BaseEventVerificationLayout>
        <template #header>
            <PageTitle
                :title="$t('Incoming requests')"
                :description="$t('Manage incoming room booking requests and event verification requests.')"
            />
        </template>

        <template #body>
            <!-- Section 1: Room booking requests -->
            <section v-if="canSeeRoomRequests" class="mb-10">
                <h2 class="text-lg font-lexend font-bold text-text">
                    {{ $t('Room booking requests') }}
                </h2>
                <p class="text-sm text-text-subtle mt-1">
                    <template v-if="roomRequestScope === 'all'">
                        {{ $t('You can see all room booking requests.') }}
                    </template>
                    <template v-else>
                        {{ $t('You can see all room booking requests for the rooms:') }}
                        {{ roomRequestScope.join(', ') }}
                    </template>
                </p>

                <div v-if="hasAnyRoomRequests" class="mt-4 space-y-6">
                    <div v-for="(requests, roomId) in roomBookingRequests" :key="roomId">
                        <h3 class="text-base font-lexend font-semibold text-text-muted mb-3">
                            {{ requests[0]?.room?.name }}
                        </h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <BaseCard elevation="raised" class="w-full" v-for="req in requests" :key="req.id">
                                <div class="p-4">
                                    <div class="flex items-center gap-x-2">
                                        <div
                                            class="w-5 h-5 rounded-full flex-shrink-0"
                                            :style="{ backgroundColor: req.event_type?.hex_code }"
                                        />
                                        <span class="font-lexend font-bold text-text truncate">
                                            {{ req.event_type?.name }}
                                        </span>
                                        <img
                                            v-if="req.audience"
                                            src="/Svgs/IconSvgs/icon_public.svg"
                                            class="h-4 w-4"
                                        />
                                        <img
                                            v-if="req.is_loud"
                                            src="/Svgs/IconSvgs/icon_loud.svg"
                                            class="h-4 w-4"
                                        />
                                    </div>

                                    <p class="mt-1 text-xs text-text-subtle font-lexend">
                                        {{ req.name }}
                                    </p>

                                    <p class="mt-1 text-xs text-text-subtle font-lexend">
                                        <span class="font-bold">{{ $t('Start') }}:</span>
                                        {{ req.start_time }}
                                        <span class="font-bold ml-2">{{ $t('End') }}:</span>
                                        {{ req.end_time }}
                                    </p>

                                    <div v-if="req.project" class="mt-1 text-xs text-text-subtle font-lexend flex items-center gap-x-1">
                                        <span>{{ $t('assigned to') }}</span>
                                        <Link
                                            :href="route('projects.tab', { project: req.project.id, projectTab: 1 })"
                                            class="text-text-subtle font-bold hover:underline"
                                        >
                                            {{ req.project?.name }}
                                        </Link>
                                    </div>
                                    <p v-else class="mt-1 text-xs text-text-subtle font-lexend">
                                        {{ $t('Not assigned to a project') }}
                                    </p>

                                    <div v-if="req.created_by" class="mt-2 flex items-center gap-x-2 text-xs text-text-subtle">
                                        <span>{{ $t('requested') }}:</span>
                                        <UserPopoverTooltip
                                            :user="req.created_by"
                                            :id="req.created_by.id"
                                            height="6"
                                            width="6"
                                        />
                                        <span>{{ req.created_at }}</span>
                                    </div>

                                    <p v-if="req.description" class="mt-2 text-xs text-text-subtle">
                                        {{ req.description }}
                                    </p>

                                    <Link
                                        :href="route('dashboard.redirect-to-calendar', { event: req.id })"
                                        class="flex items-center gap-1 mt-2 text-xs text-accent-600 hover:text-accent-700 cursor-pointer"
                                    >
                                        <IconCalendar class="h-4 w-4" />
                                        {{ $t('Show in calendar') }}
                                    </Link>

                                    <div class="flex items-center gap-x-2 mt-3">
                                        <BaseUIButton
                                            variant="secondary"
                                            hide-icon
                                            class="!bg-success-surface !text-success !border !border-success hover:!bg-success-surface capitalize text-xs font-lexend"
                                            @click="openApproveModal(req)"
                                        >
                                            <span class="flex items-center gap-x-2">
                                                <component :is="IconCheck" class="size-4" aria-hidden="true" />
                                                {{ $t('Accept') }}
                                            </span>
                                        </BaseUIButton>
                                        <BaseUIButton
                                            variant="secondary"
                                            hide-icon
                                            class="!bg-danger-surface !text-danger !border !border-danger hover:!bg-danger-surface capitalize text-xs font-lexend"
                                            @click="openDeclineModal(req)"
                                        >
                                            <span class="flex items-center gap-x-2">
                                                <component :is="IconX" class="size-4" aria-hidden="true" />
                                                {{ $t('Decline') }}
                                            </span>
                                        </BaseUIButton>
                                    </div>
                                </div>
                            </BaseCard>
                        </div>
                    </div>
                </div>
                <div v-else class="mt-3">
                    <BaseAlertComponent message="No room booking requests found" type="info" use-translation />
                </div>
            </section>

            <!-- Section 2: Verification requests -->
            <section v-if="canSeeVerifications">
                <h2 class="text-lg font-lexend font-bold text-text">
                    {{ $t('Verification requests for planned events') }}
                </h2>
                <p class="text-sm text-text-subtle mt-1">
                    <template v-if="verificationScope === 'all'">
                        {{ $t('You can see all verification requests for planned events.') }}
                    </template>
                    <template v-else>
                        {{ $t('You can see the event verification requests from:') }}
                        {{ verificationScope.join(', ') }}
                    </template>
                </p>

                <div v-if="hasAnyVerifications" class="mt-4 space-y-6">
                    <div v-for="(verifications, eventTypeId) in verificationRequests" :key="eventTypeId">
                        <h3 class="text-base font-lexend font-semibold text-text-muted mb-3">
                            {{ verifications[0]?.event?.event_type?.name }}
                        </h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <SingleEventVerificationRequest
                                v-for="v in verifications"
                                :key="v.id"
                                :event-verification="v"
                            />
                        </div>
                    </div>
                </div>
                <div v-else class="mt-3">
                    <BaseAlertComponent message="No event verification requests found" type="info" use-translation />
                </div>
            </section>

            <!-- No permissions at all -->
            <div v-if="!canSeeRoomRequests && !canSeeVerifications" class="mt-3">
                <BaseAlertComponent message="No incoming requests available for you." type="info" use-translation />
            </div>
        </template>
    </BaseEventVerificationLayout>

    <!-- Approve Room Request Modal -->
    <ArtworkBaseModal
        v-if="showApproveModal"
        title="Confirm room occupancy"
        modal-size="sm:max-w-lg"
        @close="closeApproveModal"
    >
        <p class="text-sm text-text">
            {{ $t('Are you sure you want to accept the room allocation?') }}
        </p>
            <div v-if="selectedRequest" class="mt-4 rounded-lg border border-border-subtle bg-surface-sunken px-3 py-2.5">
                <div class="flex items-center gap-x-2">
                    <span
                        class="size-4 shrink-0 rounded-full"
                        :style="{ backgroundColor: selectedRequest.event_type?.hex_code }"
                    />
                    <span class="truncate font-lexend text-sm font-bold text-text">
                        {{ selectedRequest.event_type?.name }}
                    </span>
                </div>
                <p class="mt-1 text-xs text-text-muted tabular-nums">
                    {{ selectedRequest.start_time }} - {{ selectedRequest.end_time }}
                </p>
                <p class="mt-0.5 text-xs text-text-muted">
                    {{ selectedRequest.room?.name }}
                </p>
            </div>
        <template #footer>
            <BaseUIButton
                label="No, not really"
                is-cancel-button
                hide-icon
                :disabled="processing"
                @click="closeApproveModal"
            />
            <BaseUIButton
                label="Commitments"
                variant="primary"
                icon="IconCheck"
                :processing="processing"
                @click="approveRequest"
            />
        </template>
    </ArtworkBaseModal>

    <!-- Decline Room Request Modal -->
    <ArtworkBaseModal
        v-if="showDeclineModal"
        title="Cancel room reservation"
        modal-size="sm:max-w-lg"
        @close="closeDeclineModal"
    >
        <p class="text-sm text-text">
            {{ $t('Are you sure you want to cancel the room reservation?') }}
        </p>
            <div v-if="selectedRequest" class="mt-4 rounded-lg border border-border-subtle bg-surface-sunken px-3 py-2.5">
                <div class="flex items-center gap-x-2">
                    <span
                        class="size-4 shrink-0 rounded-full"
                        :style="{ backgroundColor: selectedRequest.event_type?.hex_code }"
                    />
                    <span class="truncate font-lexend text-sm font-bold text-text">
                        {{ selectedRequest.event_type?.name }}
                    </span>
                </div>
                <p class="mt-1 text-xs text-text-muted tabular-nums">
                    {{ selectedRequest.start_time }} - {{ selectedRequest.end_time }}
                </p>
                <p class="mt-0.5 text-xs text-text-muted">
                    {{ selectedRequest.room?.name }}
                </p>
            </div>
        <template #footer>
            <BaseUIButton
                label="No, not really"
                is-cancel-button
                hide-icon
                :disabled="processing"
                @click="closeDeclineModal"
            />
            <BaseUIButton
                label="Cancellations"
                variant="danger"
                icon="IconProgressX"
                :processing="processing"
                @click="declineRequest"
            />
        </template>
    </ArtworkBaseModal>
</template>

<script setup>
import {computed, onMounted, onUnmounted, ref} from "vue";
import {router, Link, useForm, usePage} from "@inertiajs/vue3";
import {IconCheck, IconX, IconCalendar} from "@tabler/icons-vue";
import BaseEventVerificationLayout from "@/Pages/EventVerification/BaseEventVerificationLayout.vue";
import PageTitle from "@/Artwork/Titles/PageTitle.vue";
import BaseCard from "@/Artwork/Cards/BaseCard.vue";
import BaseAlertComponent from "@/Components/Alerts/BaseAlertComponent.vue";
import ArtworkBaseModal from "@/Artwork/Modals/ArtworkBaseModal.vue";
import BaseUIButton from "@/Artwork/Buttons/BaseUIButton.vue";
import UserPopoverTooltip from "@/Layouts/Components/UserPopoverTooltip.vue";
import SingleEventVerificationRequest from "@/Pages/EventVerification/Components/SingleEventVerificationRequest.vue";

const props = defineProps({
    roomBookingRequests: {
        type: Object,
        default: () => ({}),
    },
    canSeeRoomRequests: {
        type: Boolean,
        default: false,
    },
    roomRequestScope: {
        type: [String, Array],
        default: () => [],
    },
    verificationRequests: {
        type: Object,
        default: () => ({}),
    },
    canSeeVerifications: {
        type: Boolean,
        default: false,
    },
    verificationScope: {
        type: [String, Array],
        default: () => [],
    },
});

const hasAnyRoomRequests = computed(() => {
    return Object.keys(props.roomBookingRequests).length > 0 &&
        Object.values(props.roomBookingRequests).some(group => group.length > 0);
});

const hasAnyVerifications = computed(() => {
    return Object.keys(props.verificationRequests).length > 0 &&
        Object.values(props.verificationRequests).some(group => group.length > 0);
});

// Room booking request modals
const showApproveModal = ref(false);
const showDeclineModal = ref(false);
const selectedRequest = ref(null);
const processing = ref(false);

const openApproveModal = (req) => {
    selectedRequest.value = req;
    showApproveModal.value = true;
};

const closeApproveModal = () => {
    showApproveModal.value = false;
    selectedRequest.value = null;
};

const openDeclineModal = (req) => {
    selectedRequest.value = req;
    showDeclineModal.value = true;
};

const closeDeclineModal = () => {
    showDeclineModal.value = false;
    selectedRequest.value = null;
};

const approveRequest = () => {
    if (!selectedRequest.value || processing.value) return;
    processing.value = true;
    router.put(route('events.accept', {event: selectedRequest.value.id}), {accepted: true}, {
        preserveScroll: true,
        onFinish: () => {
            processing.value = false;
            closeApproveModal();
        },
    });
};

const declineRequest = () => {
    if (!selectedRequest.value || processing.value) return;
    processing.value = true;
    router.put(route('events.decline', {event: selectedRequest.value.id}), {accepted: false}, {
        preserveScroll: true,
        onFinish: () => {
            processing.value = false;
            closeDeclineModal();
        },
    });
};

// Listen for real-time updates on room channels
const subscribedChannels = ref([]);

// Neue/zurückgezogene/erledigte Prüfanfragen (BroadcastToReloadEventVerificationRequests) – der Listener
// lag nur auf "Gesendet", die Eingangsliste der Prüfenden blieb bis zum Neuladen veraltet
const verificationChannelName = `event-verification-index.${usePage().props.auth.user.id}`;
const reloadVerificationRequests = () => {
    router.reload({ only: ['verificationRequests'], preserveScroll: true });
};

onMounted(() => {
    window.Echo.private(verificationChannelName)
        .listen('.reload-event-verification-requests', reloadVerificationRequests);

    const roomIds = Object.keys(props.roomBookingRequests);
    roomIds.forEach(roomId => {
        const channel = window.Echo.private(`event.room.${roomId}`);
        channel.listen('.event.created', reloadPage);
        subscribedChannels.value.push({ roomId, channel });
    });
});

function reloadPage() {
    router.reload({ preserveScroll: true });
}

onUnmounted(() => {
    window.Echo.private(verificationChannelName)
        .stopListening('.reload-event-verification-requests', reloadVerificationRequests);
    // Nur den eigenen Handler abmelden – Echo.leave schloss den Raumkanal auch für andere Abonnenten
    subscribedChannels.value.forEach(({ channel }) => {
        channel.stopListening('.event.created', reloadPage);
    });
});
</script>

<style scoped>
</style>
