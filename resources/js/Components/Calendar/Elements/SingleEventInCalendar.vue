<template>
    <div :style="{ width: zoom_factor * 204 + 'px', minHeight: totalHeight - heightSubtraction * zoom_factor + 'px', backgroundColor: backgroundColorWithOpacity, fontsize: fontSize, lineHeight: lineHeight }"
         class="rounded-lg relative group" :class="event.occupancy_option ? 'event-disabled' : ''">
        <div v-if="zoom_factor > 0.4" class="absolute w-full h-full rounded-lg group-hover:block flex justify-center align-middle items-center z-30" :class="event.clicked ? 'block bg-green-200/50' : 'hidden bg-artwork-buttons-create/50'">
            <div class="flex justify-center items-center h-full gap-2" v-if="!multiEdit">
                <a v-if="event.project?.id && !project" type="button" :href="route('projects.tab', {project: event.project?.id, projectTab: first_project_tab_id})"
                   class="rounded-full bg-artwork-buttons-create p-1 text-white shadow-sm hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600">
                    <IconLink stroke-width="1.5" class="h-4 w-4"/>
                </a>
                <button type="button" @click="openEditEventModal(event)"
                        class="rounded-full bg-artwork-buttons-create p-1 text-white shadow-sm hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600">
                    <IconEdit class="h-4 w-4" stroke-width="1.5"/>
                </button>
                <button v-if="isRoomAdmin || isCreator || hasAdminRole" @click="openAddSubEventModal"
                        v-show="event.eventTypeId === 1"
                        type="button"
                        class="rounded-full bg-artwork-buttons-create text-white shadow-sm hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600">
                    <IconCirclePlus stroke-width="1.5" stroke="currentColor" class="w-6 h-6"/>
                </button>
                <button v-if="isRoomAdmin || isCreator || hasAdminRole"
                        type="button"
                        @click="showDeclineEventModal = true"
                        class="rounded-full bg-red-600 p-1 text-white shadow-sm hover:bg-red-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-red-600">
                    <IconX stroke-width="1.5" stroke="currentColor" class="w-4 h-4"/>
                </button>
                <button v-if="isRoomAdmin || isCreator || hasAdminRole"
                        @click="openConfirmModal(event.id, 'main')" type="button"
                        class="rounded-full bg-red-600 p-1 text-white shadow-sm hover:bg-red-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-red-600">
                    <IconTrash stroke-width="1.5" stroke="currentColor" class="w-4 h-4"/>
                </button>
            </div>
            <div v-else class="flex justify-center items-center h-full gap-2">
                <div class="relative flex items-start">
                    <div class="flex h-6 items-center">
                        <input v-model="event.clicked" id="candidates" aria-describedby="candidates-description" name="candidates" type="checkbox" class="h-5 w-5 border-gray-300 text-green-400 focus:ring-green-600"/>
                    </div>
                </div>
            </div>
        </div>
        <div class="px-1 py-1">
            <div :style="{lineHeight: lineHeight, fontSize: fontSize, color: textColorWithDarken}" :class="[zoom_factor === 1 ? 'eventHeader' : '', 'font-bold']" class="flex justify-between ">
                <div v-if="!project" class="flex items-center relative w-full">
                    <div v-if="event.eventTypeAbbreviation" class="mr-1">
                        {{ event.eventTypeAbbreviation }}:
                    </div>
                    <div :style="{ width: zoom_factor * 204 - (64 * zoom_factor) + 'px'}" class=" truncate">
                        {{ event.eventName ?? event.projectName }}
                    </div>
                    <div v-if="usePage().props.user.calendar_settings.project_status" class="absolute right-1">
                        <div v-if="event.project?.state?.color"
                             :class="[event.project.state.color,zoomFactor <= 0.8 ? 'border-2' : 'border-4']"
                             class="rounded-full">
                        </div>
                    </div>
                </div>
                <div v-else>
                    <div class="flex items-center" v-if="event.title !== event.eventTypeName">
                        <div v-if="event.eventTypeAbbreviation" class="mr-1">
                            {{ event.eventTypeAbbreviation }}:
                        </div>
                        <div :style="{ width: width - (64 * zoom_factor) + 'px'}" class=" truncate">
                            {{ event.alwaysEventName }}
                        </div>
                    </div>
                    <div v-else :style="{ width: width - (55 * zoom_factor) + 'px'}" class=" truncate">
                        {{ event.eventTypeName }}
                    </div>
                </div>
                <!-- Icon -->
                <div v-if="event.audience" class="flex">
                    <IconUsersGroup stroke-width="1.5" :width="22 * zoom_factor" :height="11 * zoom_factor"/>
                </div>
            </div>
            <!-- Time -->
            <div class="flex" :style="{lineHeight: lineHeight, fontSize: fontSize, color: textColorWithDarken}" :class="[zoom_factor === 1 ? 'eventTime' : '', 'font-medium subpixel-antialiased']">
                <div v-if="new Date(event.start).toDateString() === new Date(event.end).toDateString() && !project && !atAGlance" class="items-center">
                    <div v-if="event.allDay">
                        {{ $t('Full day') }}
                    </div>
                    <div v-else>
                        {{ new Date(event.start).format("HH:mm") + ' - ' + new Date(event.end).format("HH:mm") }}
                    </div>
                </div>
                <div class="flex w-full" v-else>
                    <div v-if="event.allDay">
                        <div
                            v-if="atAGlance && new Date(event.start).toDateString() === new Date(event.end).toDateString()">
                            {{ $t('Full day') }}, {{ new Date(event.start).format("DD.MM.") }}
                        </div>
                        <div v-else>
                            {{ $t('Full day') }}, {{ new Date(event.start).format("DD.MM.") }} - {{ new Date(event.end).format("DD.MM.") }}
                        </div>
                    </div>
                    <div v-else class="items-center">
                        <div v-if="new Date(event.start).toDateString() !== new Date(event.end).toDateString()">
                            <span class="text-error">
                                {{ new Date(event.start).toDateString() !== new Date(event.end).toDateString() ? '!' : '' }}
                            </span>
                            {{ new Date(event.start).format("DD.MM. HH:mm") + ' - ' + new Date(event.end).format("DD.MM. HH:mm") }}
                        </div>
                        <div v-else>
                            <div v-if="atAGlance">
                                {{ new Date(event.start).format("DD.MM. HH:mm") + ' - ' + new Date(event.end).format("HH:mm") }}
                            </div>
                            <div v-else>
                                {{ new Date(event.start).format("HH:mm") + ' - ' + new Date(event.end).format("HH:mm") }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>


import {computed, ref} from "vue";
import {usePage} from "@inertiajs/vue3";
import {IconCirclePlus, IconEdit, IconLink, IconTrash, IconX, IconUsersGroup} from "@tabler/icons-vue";
import Button from "@/Jetstream/Button.vue";

const zoom_factor = ref(usePage().props.user.zoom_factor ?? 1);
const atAGlance = ref(usePage().props.user.at_a_glance ?? false)

const props = defineProps({
    event: {
        type: Object,
        required: true
    },
    multiEdit: {
        type: Boolean,
        required: false,
        default: false
    },
    fontSize: {
        type: String,
        required: false,
        default: '0.875rem'
    },
    lineHeight: {
        type: String,
        required: false,
        default: '1.25rem'
    },
    first_project_tab_id: {
        type: Number,
        required: false,
        default: 1
    },
    project: {
        type: Object,
        required: false,
        default: null
    },
    hasAdminRole: {
        type: Boolean,
        required: false,
        default: false
    },
    rooms: {
        type: Object,
        required: true
    }
})

const isRoomAdmin = computed(() => {
    return props.rooms?.find(room => room.id === props.event.roomId)?.admins.some(admin => admin.id === usePage().props.user.id) || false;
})

const isCreator = computed(() => {
    return props.event?.created_by?.id === usePage().props.user.id
})

const roomCanBeBookedByEveryone = computed(() => {
    return props.rooms?.find(room => room.id === props.event.roomId).everyone_can_book
})

const backgroundColorWithOpacity = computed(() => {
    const percent = 15;
    const color = props.event.event_type_color;
    if (!color) return `rgb(255, 255, 255, ${percent}%)`;
    return `rgb(${parseInt(color.slice(-6, -4), 16)}, ${parseInt(color.slice(-4, -2), 16)}, ${parseInt(color.slice(-2), 16)}, ${percent}%)`;
})

const textColorWithDarken = computed(() => {
    const percent = 75;
    const color = props.event.event_type_color;
    if (!color) return 'rgb(180, 180, 180)';
    return `rgb(${parseInt(color.slice(-6, -4), 16) - percent}, ${parseInt(color.slice(-4, -2), 16) - percent}, ${parseInt(color.slice(-2), 16) - percent})`;
})

const totalHeight = computed(() => {
    let height = 42;
    // ProjectStatus is in same row as name -> no extra height needed
    if (usePage().props.user.calendar_settings.project_status) height += 0;
    //Options are in same row as time -> no extra height needed
    if (usePage().props.user.calendar_settings.options) height += 0;
    if (usePage().props.user.calendar_settings.project_management) height += 17;
    if (usePage().props.user.calendar_settings.repeating_events) height += 20;
    if (usePage().props.user.calendar_settings.work_shifts) height += 18;
    return height;
})

const heightSubtraction = computed(() => {
    let heightSubtraction = 0;
    if (usePage().props.user.calendar_settings.project_management && (!props.event.projectLeaders || props.event.projectLeaders?.length < 1)) {
        heightSubtraction += 17;
    }
    if (usePage().props.user.calendar_settings.repeating_events && (!props.event.is_series || props.event.is_series === false)) {
        heightSubtraction += 20;
    }
    if (usePage().props.user.calendar_settings.work_shifts && (!props.event.shifts || props.event.shifts?.length < 1)) {
        heightSubtraction += 18;
    }
    return heightSubtraction;
})

</script>

<style scoped>

</style>