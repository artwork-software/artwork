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
                <h2 class="text-lg font-lexend font-bold text-gray-900">
                    {{ $t('Room booking requests') }}
                </h2>
                <p class="text-sm text-secondary mt-1">
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
                        <h3 class="text-base font-lexend font-semibold text-gray-700 mb-3">
                            {{ requests[0]?.room?.name }}
                        </h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <WhiteInnerCard v-for="req in requests" :key="req.id">
                                <div class="p-4">
                                    <div class="flex items-center gap-x-2">
                                        <div
                                            class="w-5 h-5 rounded-full flex-shrink-0"
                                            :style="{ backgroundColor: req.event_type?.hex_code }"
                                        />
                                        <span class="font-lexend font-bold text-gray-900 truncate">
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

                                    <p class="mt-1 text-xs text-gray-500 font-lexend">
                                        {{ req.name }}
                                    </p>

                                    <p class="mt-1 text-xs text-gray-500 font-lexend">
                                        <span class="font-bold">{{ $t('Start') }}:</span>
                                        {{ req.start_time }}
                                        <span class="font-bold ml-2">{{ $t('End') }}:</span>
                                        {{ req.end_time }}
                                    </p>

                                    <div v-if="req.project" class="mt-1 text-xs text-gray-500 font-lexend flex items-center gap-x-1">
                                        <span>{{ $t('assigned to') }}</span>
                                        <Link
                                            :href="route('projects.tab', { project: req.project.id, projectTab: 1 })"
                                            class="text-secondary font-bold hover:underline"
                                        >
                                            {{ req.project?.name }}
                                        </Link>
                                    </div>
                                    <p v-else class="mt-1 text-xs text-gray-400 font-lexend">
                                        {{ $t('Not assigned to a project') }}
                                    </p>

                                    <div v-if="req.created_by" class="mt-2 flex items-center gap-x-2 text-xs text-gray-400">
                                        <span>{{ $t('requested') }}:</span>
                                        <UserPopoverTooltip
                                            :user="req.created_by"
                                            :id="req.created_by.id"
                                            height="6"
                                            width="6"
                                        />
                                        <span>{{ req.created_at }}</span>
                                    </div>

                                    <p v-if="req.description" class="mt-2 text-xs text-gray-500">
                                        {{ req.description }}
                                    </p>

                                    <Link
                                        :href="route('dashboard.redirect-to-calendar', { event: req.id })"
                                        class="flex items-center gap-1 mt-2 text-xs text-indigo-600 hover:text-indigo-800 cursor-pointer"
                                    >
                                        <IconCalendar class="h-4 w-4" />
                                        {{ $t('Show in calendar') }}
                                    </Link>

                                    <div class="flex items-center gap-x-2 mt-3">
                                        <BaseCardButton
                                            text="Accept"
                                            class="!bg-green-50 !text-green-600 !border !border-green-600 hover:!bg-green-100 capitalize text-xs font-lexend"
                                            @click="openApproveModal(req)"
                                        >
                                            <component :is="IconCheck" class="size-4" aria-hidden="true" />
                                        </BaseCardButton>
                                        <BaseCardButton
                                            text="Decline"
                                            class="!bg-red-50 !text-red-600 !border !border-red-600 hover:!bg-red-100 capitalize text-xs font-lexend"
                                            @click="openDeclineModal(req)"
                                        >
                                            <component :is="IconX" class="size-4" aria-hidden="true" />
                                        </BaseCardButton>
                                    </div>
                                </div>
                            </WhiteInnerCard>
                        </div>
                    </div>
                </div>
                <div v-else class="mt-3">
                    <BaseAlertComponent message="No room booking requests found" type="info" use-translation />
                </div>
            </section>

            <!-- Section 2: Verification requests -->
            <section v-if="canSeeVerifications">
                <h2 class="text-lg font-lexend font-bold text-gray-900">
                    {{ $t('Verification requests for planned events') }}
                </h2>
                <p class="text-sm text-secondary mt-1">
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
                        <h3 class="text-base font-lexend font-semibold text-gray-700 mb-3">
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
    <BaseModal v-if="showApproveModal" modal-image="/Svgs/Overlays/illu_success.svg" @closed="closeApproveModal">
        <div class="mx-4">
            <div class="headline1 my-2">
                {{ $t('Confirm room occupancy') }}
            </div>
            <div class="successText">
                {{ $t('Are you sure you want to accept the room allocation?') }}
            </div>
            <div v-if="selectedRequest" class="mt-4">
                <div class="flex items-center gap-x-2">
                    <div
                        class="w-5 h-5 rounded-full flex-shrink-0"
                        :style="{ backgroundColor: selectedRequest.event_type?.hex_code }"
                    />
                    <span class="font-lexend font-bold text-gray-900">
                        {{ selectedRequest.event_type?.name }}
                    </span>
                </div>
                <p class="mt-1 text-xs text-gray-500 font-lexend">
                    {{ selectedRequest.start_time }} - {{ selectedRequest.end_time }}
                </p>
                <p class="mt-1 text-xs text-gray-500 font-lexend">
                    {{ selectedRequest.room?.name }}
                </p>
            </div>
            <div class="flex justify-between mt-6">
                <FormButton
                    :text="$t('Commitments')"
                    @click="approveRequest"
                />
                <div class="flex my-auto">
                    <span class="xsLight cursor-pointer" @click="closeApproveModal">
                        {{ $t('No, not really') }}
                    </span>
                </div>
            </div>
        </div>
    </BaseModal>

    <!-- Decline Room Request Modal -->
    <BaseModal v-if="showDeclineModal" modal-image="/Svgs/Overlays/illu_warning.svg" @closed="closeDeclineModal">
        <div class="mx-4">
            <div class="headline1 my-2">
                {{ $t('Cancel room reservation') }}
            </div>
            <div class="errorText">
                {{ $t('Are you sure you want to cancel the room reservation?') }}
            </div>
            <div v-if="selectedRequest" class="mt-4">
                <div class="flex items-center gap-x-2">
                    <div
                        class="w-5 h-5 rounded-full flex-shrink-0"
                        :style="{ backgroundColor: selectedRequest.event_type?.hex_code }"
                    />
                    <span class="font-lexend font-bold text-gray-900">
                        {{ selectedRequest.event_type?.name }}
                    </span>
                </div>
                <p class="mt-1 text-xs text-gray-500 font-lexend">
                    {{ selectedRequest.start_time }} - {{ selectedRequest.end_time }}
                </p>
                <p class="mt-1 text-xs text-gray-500 font-lexend">
                    {{ selectedRequest.room?.name }}
                </p>
            </div>
            <div class="flex justify-between mt-6">
                <BaseUIButton
                    is-delete-button
                    icon="IconProgressX"
                    :label="$t('Cancellations')"
                    :disabled="processing"
                    @click="declineRequest"
                />
                <div class="flex my-auto">
                    <span class="xsLight cursor-pointer" @click="closeDeclineModal">
                        {{ $t('No, not really') }}
                    </span>
                </div>
            </div>
        </div>
    </BaseModal>
</template>

<script setup>
import {computed, onMounted, onUnmounted, ref} from "vue";
import {router, Link, useForm} from "@inertiajs/vue3";
import {IconCheck, IconX, IconCalendar} from "@tabler/icons-vue";
import BaseEventVerificationLayout from "@/Pages/EventVerification/BaseEventVerificationLayout.vue";
import PageTitle from "@/Artwork/Titles/PageTitle.vue";
import WhiteInnerCard from "@/Artwork/Cards/WhiteInnerCard.vue";
import BaseAlertComponent from "@/Components/Alerts/BaseAlertComponent.vue";
import BaseCardButton from "@/Artwork/Buttons/BaseCardButton.vue";
import BaseModal from "@/Components/Modals/BaseModal.vue";
import BaseUIButton from "@/Artwork/Buttons/BaseUIButton.vue";
import FormButton from "@/Layouts/Components/General/Buttons/FormButton.vue";
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
    if (!selectedRequest.value) return;
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
    if (!selectedRequest.value) return;
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

onMounted(() => {
    const roomIds = Object.keys(props.roomBookingRequests);
    roomIds.forEach(roomId => {
        const channel = window.Echo.private(`event.room.${roomId}`);
        channel.listen('.event.created', () => {
            router.reload({ preserveScroll: true });
        });
        subscribedChannels.value.push({ roomId, channel });
    });
});

onUnmounted(() => {
    subscribedChannels.value.forEach(({ roomId }) => {
        window.Echo.leave(`event.room.${roomId}`);
    });
});
</script>

<style scoped>
</style>
