
<template>
    <div class="flex w-full border border-border-subtle rounded-lg bg-white shadow-sm">
        <div class="ml-2 w-full p-2">
            <!-- Event type + name row -->
            <div class="w-full flex cursor-pointer truncate">
                <div>
                    <div class="block w-10 h-10 rounded-full" :style="{'backgroundColor' : eventTypes.find(type => type.id === event.eventTypeId)?.hex_code }" />
                </div>
                <p class="ml-2 headline2 flex items-center">
                    {{ eventTypes.find(type => type.id === event.eventTypeId)?.name }}
                </p>
                <div class="flex w-1/2 ml-12 xsDark items-center">
                    {{ event.eventName }}
                </div>
            </div>

            <!-- Project + Creator row -->
            <div class="w-full flex mt-1">
                <div class="flex w-1/2 xxsDark items-center my-auto" v-if="event.projectId">
                    {{$t('Project')}}:
                    <a :href="route('projects.tab', {project: event.projectId, projectTab: first_project_calendar_tab_id})"
                       class="ml-1 xxsDarkBold items-center flex">
                        {{ event.projectName }}
                    </a>
                </div>
                <div class="flex items-center w-1/2 mb-1">
                    <div class="truncate flex xxsDark max-w-60 mt-1">
                        {{$t('Created by')}}
                        <div class="xxsDarkBold ml-1"> {{ event.created_by.first_name }}
                            {{ event.created_by.last_name }}
                        </div>
                    </div>
                    <img
                        :data-tooltip-target="event.created_by.id"
                        :src="event.created_by.profile_photo_url"
                        :alt="event.created_by.last_name"
                        class="ml-2 ring-white ring-2 rounded-full h-9 w-9 object-cover"/>
                </div>
            </div>

            <!-- Date/Time + Declined room row -->
            <div class="my-2">
                <div class="flex" v-if="event.startDate === event.endDate">
                    <div class="xsDark flex">
                        <p v-if="declinedRoomName"
                           class="text-error mr-1 line-through">
                            {{ declinedRoomName }},
                        </p>
                        {{ formatDisplayDate(event.startDate) }},
                        {{ event.allDay ? $t('Full day') : event.startTime + "-" + event.endTime }}
                    </div>
                </div>
                <div v-else>
                    <p v-if="declinedRoomName"
                       class="text-error line-through">
                        {{ declinedRoomName }},
                    </p>
                    <div class="xsDark">
                        {{ formatDisplayDate(event.startDate) }},
                        {{ event.allDay ? $t('Full day') : event.startTime }} -
                        {{ formatDisplayDate(event.endDate) }},
                        {{ event.allDay ? $t('Full day') : event.endTime }}
                    </div>
                </div>
            </div>

            <!-- Action buttons -->
            <div class="flex items-center gap-3 mt-2 mb-2">
                <button
                    v-if="canEditEvent"
                    @click="$emit('editEvent', event)"
                    class="flex items-center gap-1 text-xs text-accent-600 hover:text-accent-700 cursor-pointer"
                    type="button"
                >
                    <IconPencil class="h-4 w-4" />
                    {{ $t('Edit event') }}
                </button>
                <button
                    v-if="canEditEvent"
                    class="flex items-center gap-1 text-xs text-text-subtle hover:text-danger cursor-pointer"
                    @click="openDeleteEventModal(event)"
                    type="button"
                >
                    <IconTrash class="h-4 w-4" />
                    {{ $t('Delete') }}
                </button>
            </div>
        </div>
    </div>
    <confirmation-component
        v-if="deleteComponentVisible"
        :confirm="$t('Delete')"
        :titel="$t('Delete event')"
        :description="$t('Are you sure you want to put the event {0} in the trash? You can restore it within 30 days.', [event.eventName] )"
        @closed="afterConfirm"/>
</template>

<script setup>
import {
    IconTrash,
    IconPencil
} from "@tabler/icons-vue";
import ConfirmationComponent from "@/Layouts/Components/ConfirmationComponent.vue";
import {computed, ref} from "vue";
import {router, usePage} from "@inertiajs/vue3";

const props = defineProps([
    'eventTypes',
    'rooms',
    'isAdmin',
    'first_project_calendar_tab_id',
    'event',
    'eventStatuses'
]);

const emit = defineEmits(['editEvent']);

const page = usePage();
const deleteComponentVisible = ref(false);
const eventToDelete = ref(null);

const canEditEvent = computed(() => {
    return props.event?.user_id === page.props.auth.user.id
        || props.event?.created_by?.id === page.props.auth.user.id
        || props.isAdmin;
});

const declinedRoomName = computed(() => {
    if (!props.event?.declinedRoomId) return null;
    return props.rooms?.find(room => room.id === props.event.declinedRoomId)?.name ?? null;
});

function formatDisplayDate(dateStr) {
    if (!dateStr) return '-';
    const parts = String(dateStr).substring(0, 10).split('-');
    if (parts.length !== 3) return dateStr;
    return `${parts[2]}.${parts[1]}.${parts[0]}`;
}

function openDeleteEventModal(event) {
    eventToDelete.value = event;
    deleteComponentVisible.value = true;
}

function afterConfirm(bool) {
    if (!bool) return deleteComponentVisible.value = false;

    router.delete(`/events/${eventToDelete.value.id}`, {
        preserveScroll: true,
        preserveState: true,
    });
}
</script>
