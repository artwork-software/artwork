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
                    <span v-if="roomRequestCount > 0" class="ml-1 text-sm font-normal text-text-subtle tabular-nums">({{ roomRequestCount }})</span>
                    <button v-if="roomRequestCount > 1" type="button"
                            class="ml-3 text-sm font-normal text-accent-600 hover:text-accent-700"
                            @click="selectedRoomRequestIds = toggleAllKeys(selectedRoomRequestIds, allRoomRequestIds)">
                        {{ allRoomRequestsSelected ? $t('Clear selection') : $t('Select all') }}
                    </button>
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
                                        <SelectionCheckbox
                                            :checked="selectedRoomRequestIds.includes(req.id)"
                                            :label="$t('Select {name}', { name: req.name || req.event_type?.name || '' })"
                                            @toggle="selectedRoomRequestIds = toggleKey(selectedRoomRequestIds, req.id)"
                                        />
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
                                            :alt="$t('With audience')"
                                            :title="$t('With audience')"
                                        />
                                        <img
                                            v-if="req.is_loud"
                                            src="/Svgs/IconSvgs/icon_loud.svg"
                                            class="h-4 w-4"
                                            :alt="$t('It gets loud')"
                                            :title="$t('It gets loud')"
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
                                            v-if="first_project_tab_id"
                                            :href="route('projects.tab', { project: req.project.id, projectTab: first_project_tab_id })"
                                            class="text-text-subtle font-bold hover:underline"
                                        >
                                            {{ req.project?.name }}
                                        </Link>
                                        <span v-else class="font-bold">{{ req.project?.name }}</span>
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
                    <span v-if="verificationCount > 0" class="ml-1 text-sm font-normal text-text-subtle tabular-nums">({{ verificationCount }})</span>
                    <button v-if="verificationCount > 1" type="button"
                            class="ml-3 text-sm font-normal text-accent-600 hover:text-accent-700"
                            @click="selectedVerificationEventIds = toggleAllKeys(selectedVerificationEventIds, allVerificationEventIds)">
                        {{ allVerificationsSelected ? $t('Clear selection') : $t('Select all') }}
                    </button>
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
                            >
                                <template #select>
                                    <SelectionCheckbox
                                        v-if="v.event?.id"
                                        class="mt-0.5"
                                        :checked="selectedVerificationEventIds.includes(v.event.id)"
                                        :label="$t('Select {name}', { name: v.event.eventName ?? v.event.project?.name ?? '' })"
                                        @toggle="selectedVerificationEventIds = toggleKey(selectedVerificationEventIds, v.event.id)"
                                    />
                                </template>
                            </SingleEventVerificationRequest>
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

    <!-- Leiste der Mehrfachauswahl -->
    <div v-if="selectedRoomRequestIds.length > 0 || selectedVerificationEventIds.length > 0"
         class="pointer-events-none fixed inset-x-0 bottom-6 z-40 flex justify-center px-4">
        <div class="pointer-events-auto flex flex-wrap items-center gap-x-3 gap-y-2 rounded-xl bg-surface-inverse py-2.5 pl-4 pr-2 text-sm text-white shadow-lg">
            <template v-if="selectedRoomRequestIds.length > 0">
                <span class="text-white/70 tabular-nums">
                    {{ $t('Room booking requests') }}: {{ $t('{count} selected', { count: selectedRoomRequestIds.length }) }}
                </span>
                <button type="button" :class="bulkButtonClass" :disabled="bulkProcessing" @click="bulkConfirm = 'accept'">
                    <IconCheck class="size-4" />
                    {{ $t('Commitments') }}
                </button>
                <button type="button" :class="bulkDangerButtonClass" :disabled="bulkProcessing" @click="bulkConfirm = 'decline'">
                    <IconX class="size-4" />
                    {{ $t('Cancellations') }}
                </button>
            </template>
            <template v-if="selectedVerificationEventIds.length > 0">
                <span class="text-white/70 tabular-nums">
                    {{ $t('Verification requests') }}: {{ $t('{count} selected', { count: selectedVerificationEventIds.length }) }}
                </span>
                <button type="button" :class="bulkButtonClass" :disabled="bulkProcessing" @click="approveSelectedVerifications">
                    <IconCheck class="size-4" />
                    {{ $t('Approve') }}
                </button>
                <button type="button" :class="bulkDangerButtonClass" :disabled="bulkProcessing" @click="showBulkReject = true">
                    <IconX class="size-4" />
                    {{ $t('Reject') }}
                </button>
            </template>
            <button type="button" class="rounded-lg p-1.5 hover:bg-white/15" :title="$t('Clear selection')" :aria-label="$t('Clear selection')" @click="clearSelection">
                <IconX class="size-4" />
            </button>
        </div>
    </div>

    <!-- Rückfrage der Sammelantwort auf Raumbelegungsanfragen -->
    <ArtworkBaseModal
        v-if="bulkConfirm"
        :title="bulkConfirm === 'accept' ? 'Confirm room occupancy' : 'Cancel room reservation'"
        modal-size="sm:max-w-lg"
        @close="bulkConfirm = null"
    >
        <p class="text-sm text-text">
            {{ bulkConfirm === 'accept'
                ? $t('Accept the {count} selected room booking requests?', { count: selectedRoomRequestIds.length })
                : $t('Decline the {count} selected room booking requests?', { count: selectedRoomRequestIds.length }) }}
        </p>
        <template #footer>
            <BaseUIButton label="No, not really" is-cancel-button hide-icon :disabled="bulkProcessing" @click="bulkConfirm = null" />
            <BaseUIButton
                :label="bulkConfirm === 'accept' ? 'Commitments' : 'Cancellations'"
                :variant="bulkConfirm === 'accept' ? 'primary' : 'danger'"
                :icon="bulkConfirm === 'accept' ? 'IconCheck' : 'IconProgressX'"
                :processing="bulkProcessing"
                @click="answerSelectedRoomRequests"
            />
        </template>
    </ArtworkBaseModal>

    <!-- Ablehnungsgrund für mehrere Überprüfungsanfragen -->
    <ArtworkBaseModal
        v-if="showBulkReject"
        title="Reject Request"
        modal-size="sm:max-w-lg"
        @close="showBulkReject = false"
    >
        <p class="mb-3 text-sm text-text">
            {{ $t('Reject the {count} selected verification requests? The reason applies to all of them.', { count: selectedVerificationEventIds.length }) }}
        </p>
        <BaseTextarea id="bulk_rejection_reason" v-model="bulkRejectionReason" label="Rejection Reason" :maxlength="16000" />
        <template #footer>
            <BaseUIButton label="No, not really" is-cancel-button hide-icon :disabled="bulkProcessing" @click="showBulkReject = false" />
            <BaseUIButton label="Reject Request" variant="danger" icon="IconCircleX" :processing="bulkProcessing" @click="rejectSelectedVerifications" />
        </template>
    </ArtworkBaseModal>

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
import {computed, onMounted, onUnmounted, ref, watch} from "vue";
import axios from "axios";
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
import SelectionCheckbox from "@/Pages/Areas/Components/SelectionCheckbox.vue";
import BaseTextarea from "@/Artwork/Inputs/BaseTextarea.vue";
import {pruneKeys, selectionState, toggleAllKeys, toggleKey} from "@/Pages/Trash/Composables/trashTools.js";
import {setIncomingRequestCount} from "@/Composeables/incomingRequestCount.js";

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
    first_project_tab_id: {
        type: [Number, String],
        default: null,
    },
});

const roomRequestCount = computed(() => Object.values(props.roomBookingRequests)
    .reduce((sum, group) => sum + group.length, 0));
const verificationCount = computed(() => Object.values(props.verificationRequests)
    .reduce((sum, group) => sum + group.length, 0));

// --- Mehrfachauswahl -----------------------------------------------------------------------
const selectedRoomRequestIds = ref([]);
const selectedVerificationEventIds = ref([]);
const bulkProcessing = ref(false);
const bulkConfirm = ref(null);
const showBulkReject = ref(false);
const bulkRejectionReason = ref('');
const bulkButtonClass = 'inline-flex items-center gap-1.5 rounded-lg bg-white/15 px-3 py-1 font-semibold hover:bg-white/25 disabled:opacity-50';
const bulkDangerButtonClass = 'inline-flex items-center gap-1.5 rounded-lg bg-danger px-3 py-1 font-semibold hover:opacity-90 disabled:opacity-50';

const allRoomRequestIds = computed(() => Object.values(props.roomBookingRequests).flat().map((req) => req.id));
const allVerificationEventIds = computed(() => [...new Set(
    Object.values(props.verificationRequests).flat().map((v) => v.event?.id).filter(Boolean)
)]);
const allRoomRequestsSelected = computed(() => selectionState(selectedRoomRequestIds.value, allRoomRequestIds.value) === 'all');
const allVerificationsSelected = computed(() => selectionState(selectedVerificationEventIds.value, allVerificationEventIds.value) === 'all');

// beantwortete Anfragen fallen aus der Auswahl; Reiter und Seitenmenü bekommen die aktuelle Zahl
watch([allRoomRequestIds, allVerificationEventIds], ([roomIds, eventIds]) => {
    selectedRoomRequestIds.value = pruneKeys(selectedRoomRequestIds.value, roomIds);
    selectedVerificationEventIds.value = pruneKeys(selectedVerificationEventIds.value, eventIds);
    setIncomingRequestCount(roomRequestCount.value + verificationCount.value);
}, { immediate: true });

const clearSelection = () => {
    selectedRoomRequestIds.value = [];
    selectedVerificationEventIds.value = [];
};

const answerSelectedRoomRequests = () => {
    if (bulkProcessing.value || !bulkConfirm.value) return;
    bulkProcessing.value = true;
    router.post(route('events.requests.bulk-answer'), {
        events: selectedRoomRequestIds.value,
        action: bulkConfirm.value,
    }, {
        preserveScroll: true,
        onFinish: () => {
            bulkProcessing.value = false;
            bulkConfirm.value = null;
        },
    });
};

// Die Sammelrouten der Überprüfung antworten ohne Weiterleitung; danach die Listen neu laden
// (übersprungene Termine meldet der Server als Hinweis beim Neuladen)
const answerSelectedVerifications = (routeName, payload) => {
    if (bulkProcessing.value) return;
    bulkProcessing.value = true;
    axios.post(route(routeName), { events: selectedVerificationEventIds.value, ...payload })
        .then(() => {
            showBulkReject.value = false;
            bulkRejectionReason.value = '';
        })
        .catch(() => {})
        .finally(() => {
            bulkProcessing.value = false;
            router.reload({ preserveScroll: true });
        });
};
const approveSelectedVerifications = () => answerSelectedVerifications('event-verifications.approved-by-events', {});
const rejectSelectedVerifications = () => answerSelectedVerifications(
    'event-verifications.reject-by-events',
    { rejection_reason: bulkRejectionReason.value },
);

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

// Live-Aktualisierung: ein gemeinsamer Hinweis-Kanal für Raumbelegungsanfragen (vorher ein Kanal je Raum,
// und nur für Räume, die beim Laden schon eine Anfrage hatten) und der eigene Kanal der Prüfanfragen
const ROOM_REQUEST_CHANNEL = 'room-requests.inbox';
const verificationChannelName = `event-verification-index.${usePage().props.auth.user.id}`;

const reloadRoomRequests = () => {
    router.reload({ only: ['roomBookingRequests'], preserveScroll: true });
};
// Neue/zurückgezogene/erledigte Prüfanfragen (BroadcastToReloadEventVerificationRequests)
const reloadVerificationRequests = () => {
    router.reload({ only: ['verificationRequests'], preserveScroll: true });
};

onMounted(() => {
    window.Echo.private(verificationChannelName)
        .listen('.reload-event-verification-requests', reloadVerificationRequests);
    if (props.canSeeRoomRequests) {
        window.Echo.private(ROOM_REQUEST_CHANNEL)
            .listen('.room-requests.changed', reloadRoomRequests);
    }
});

// Nur die eigenen Handler abmelden – die Kanäle können auch andere Abonnenten haben
onUnmounted(() => {
    window.Echo.private(verificationChannelName)
        .stopListening('.reload-event-verification-requests', reloadVerificationRequests);
    if (props.canSeeRoomRequests) {
        window.Echo.private(ROOM_REQUEST_CHANNEL)
            .stopListening('.room-requests.changed', reloadRoomRequests);
    }
});
</script>

<style scoped>
</style>
