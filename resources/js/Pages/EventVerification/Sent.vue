<template>
    <BaseEventVerificationLayout>
        <template #header>
            <PageTitle
                :title="$t('Requests sent by me')"
                :description="$t('Manage your event verification requests. You can add, edit, or delete your event verification requests here.')"
            />
        </template>

        <template #body>
            <div class="grid grid-cols-6 gap-10">
                <div class="col-span-4">
                    <!-- Section 1: My room booking requests -->
                    <section v-if="myRoomRequests.length > 0" class="mb-10">
                        <h2 class="text-lg font-lexend font-bold text-gray-900 mb-3">
                            {{ $t('My room booking requests') }}
                        </h2>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <WhiteInnerCard v-for="req in myRoomRequests" :key="req.id">
                                <div class="p-4">
                                    <div class="flex items-center gap-x-2">
                                        <div
                                            class="w-5 h-5 rounded-full flex-shrink-0"
                                            :style="{ backgroundColor: req.event_type?.hex_code }"
                                        />
                                        <span class="font-lexend font-bold text-gray-900 truncate">
                                            {{ req.event_type?.name }}
                                        </span>
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
                                    <p v-if="req.room" class="mt-1 text-xs text-gray-500 font-lexend">
                                        <span class="font-bold">{{ $t('Room') }}:</span>
                                        {{ req.room?.name }}
                                    </p>
                                    <p v-if="isDeclined(req)" class="mt-1 text-xs text-gray-500 font-lexend">
                                        <span class="font-bold">{{ $t('Declined room') }}:</span>
                                        <span class="line-through">{{ req.declined_room_name }}</span>
                                    </p>
                                    <div class="mt-2 flex items-center justify-between">
                                        <span v-if="isDeclined(req)"
                                              class="inline-flex items-center rounded-md bg-red-50 px-2 py-1 text-xs font-medium text-red-700 ring-1 ring-inset ring-red-600/10">
                                            {{ $t('declined') }}
                                        </span>
                                        <span v-else
                                              class="inline-flex items-center rounded-md bg-yellow-50 px-2 py-1 text-xs font-medium text-yellow-800 ring-1 ring-inset ring-yellow-600/20">
                                            {{ $t('pending') }}
                                        </span>
                                        <button
                                            v-if="isDeclined(req)"
                                            @click="openEditModal(req)"
                                            class="flex items-center gap-1 text-xs text-indigo-600 hover:text-indigo-800 cursor-pointer"
                                        >
                                            <IconPencil class="h-4 w-4" />
                                            {{ $t('Edit event') }}
                                        </button>
                                    </div>
                                </div>
                            </WhiteInnerCard>
                        </div>
                    </section>

                    <!-- Section 2: My verification requests -->
                    <section>
                        <h2 class="text-lg font-lexend font-bold text-gray-900 mb-3">
                            {{ $t('My Verification Requests') }}
                        </h2>
                        <div v-if="myRequests.data.length > 0">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div v-for="myRequest in myRequests.data" :key="myRequest.id">
                                    <SingleMyEventVerificationRequests :my-request="myRequest" />
                                </div>
                            </div>

                            <div class="mt-5">
                                <BasePaginator property-name="myRequests" :entities="myRequests" use-custom-url-params />
                            </div>
                        </div>
                        <div v-else class="mt-3">
                            <BaseAlertComponent message="No event verification requests found" type="error" use-translation />
                        </div>
                    </section>
                </div>
                <div class="col-start-5 col-span-2">
                    <BaseCard>
                        <CardHeadline title="Planned Events" description="You can see all planned events here. You can also request a verification directly here." />
                        <div class="pb-5">
                            <div class="space-y-4 max-h-96 overflow-x-auto px-5 pb-5" v-if="plannedEvents.length > 0">
                                <div v-for="(event, index) in plannedEvents" :key="event.id">
                                    <SinglePlannedEventInVerificationPage :event="event" />
                                </div>
                            </div>
                            <div v-else class="px-5">
                                <BaseAlertComponent message="No planned events found" type="error" use-translation />
                            </div>
                        </div>
                    </BaseCard>
                </div>
            </div>

            <!-- Edit declined event modal -->
            <EventsWithoutRoomComponent
                v-if="showEditModal"
                :event-types="eventTypes"
                :rooms="rooms"
                :events-without-room="editModalEvents"
                :is-admin="hasAdminRole()"
                :first_project_calendar_tab_id="first_project_calendar_tab_id"
                :event-statuses="eventStatuses"
                @closed="onEditModalClose"
            />
        </template>
    </BaseEventVerificationLayout>
</template>

<script setup>
import SingleMyEventVerificationRequests
    from "@/Pages/EventVerification/Components/SingleMyEventVerificationRequests.vue";
import BasePaginator from "@/Components/Paginate/BasePaginator.vue";
import {onMounted, provide, ref} from "vue";
import BaseAlertComponent from "@/Components/Alerts/BaseAlertComponent.vue";
import {router, usePage} from "@inertiajs/vue3";
import BaseCard from "@/Artwork/Cards/BaseCard.vue";
import CardHeadline from "@/Artwork/Cards/CardHeadline.vue";
import BaseEventVerificationLayout from "@/Pages/EventVerification/BaseEventVerificationLayout.vue";
import PageTitle from "@/Artwork/Titles/PageTitle.vue";
import SinglePlannedEventInVerificationPage
    from "@/Pages/EventVerification/Components/SinglePlannedEventInVerificationPage.vue";
import WhiteInnerCard from "@/Artwork/Cards/WhiteInnerCard.vue";
import EventsWithoutRoomComponent from "@/Layouts/Components/EventsWithoutRoomComponent.vue";
import {IconPencil} from "@tabler/icons-vue";
import {usePermission} from "@/Composeables/Permission.js";

const {hasAdminRole} = usePermission(usePage().props);

const props = defineProps({
    myRequests: {
        type: Object,
        required: true
    },
    plannedEvents: {
        type: Object,
        required: true
    },
    myRoomRequests: {
        type: Array,
        default: () => [],
    },
    myRoomRequestsForEdit: {
        type: Array,
        default: () => [],
    },
    rooms: {
        type: Array,
        default: () => [],
    },
    eventTypes: {
        type: Array,
        default: () => [],
    },
    eventStatuses: {
        type: Array,
        default: () => [],
    },
    first_project_calendar_tab_id: {
        type: [Number, String],
        default: null,
    },
    event_properties: {
        type: Array,
        default: () => [],
    },
})

provide('event_properties', props.event_properties);

const showEditModal = ref(false);
const editModalEvents = ref([]);

const isDeclined = (req) => {
    return !req.room && req.declined_room_id;
};

const openEditModal = (req) => {
    const rawEvent = props.myRoomRequestsForEdit.find(e => e.id === req.id);
    if (rawEvent) {
        editModalEvents.value = [rawEvent];
        showEditModal.value = true;
    }
};

const onEditModalClose = () => {
    showEditModal.value = false;
    editModalEvents.value = [];
    router.reload({ preserveScroll: true });
};

const showNewRequestIncoming = ref(false);

onMounted(() => {
    window.Echo.private(`event-verification-index.${usePage().props.auth.user.id}`)
        .listen('.reload-event-verification-requests', (e) => {
            router.reload({
                preserveScroll: true,
                preserveState: false,
                only: ['myRequests', 'myRoomRequests', 'myRoomRequestsForEdit'],
                data: {
                    page: 1,
                },
                onSuccess: (e) => {
                    showNewRequestIncoming.value = true;
                    setTimeout(() => {
                        showNewRequestIncoming.value = false;
                    }, 5000);
                }
            })
        });
})
</script>

<style scoped>
</style>
