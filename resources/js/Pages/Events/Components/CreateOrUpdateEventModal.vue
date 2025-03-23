<template>
    <BaseModal @closed="$emit('closed')" full-modal>

        <form @submit.prevent="UpdateOrCreateEvent" class="divide-y divide-dashed divide-gray-300 relative">
            <div class=" absolute w-full h-full bg-white z-10 p-8 rounded-lg shadow-lg flex items-center justify-center flex-col gap-y-3" v-if="modalIsLoading">
                <svg class="animate-spin h-8 w-8 text-artwork-buttons-create" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
                </svg>
                <div class="headline3">{{ $t('Load window data') }}</div>
            </div>
            <div>
                <div class="flex items-center justify-between px-6 pt-5">
                    <ModalHeader
                        classes="!mb-3"
                        :title="eventForm.id ? $t('Edit Event') : $t('Create Event')"
                        :description="$t('Please make sure that you allow for preparation and follow-up time.')"
                    />
                    <div v-if="eventForm.id" class="flex items-center xsDark">
                        {{ $t('Created by') }}
                        <div>
                            <UserPopoverTooltip
                                :user="eventToEdit.created_by"
                                :id="eventToEdit.created_by?.id ?? 'deletedUserTooltip'"
                                height="7"
                                width="7"
                                class="ml-2"
                            />
                        </div>
                    </div>
                </div>
                <div class="px-6 py-6 space-y-4">
                    <!-- Event Type, Name and Status if needed -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <Listbox as="div" class="-mt-1 relative" v-model="selectedEventType" id="eventType">
                                <ListboxLabel class="xsLight mb-2">{{ $t('Event type') }}</ListboxLabel>
                                <ListboxButton class="menu-button">
                                    <div class="flex w-full justify-between">
                                        <div class="flex items-center gap-x-2">
                                            <div>
                                                <div class="block w-5 h-5 rounded-full" :style="{'backgroundColor' : selectedEventType?.hex_code }"/>
                                            </div>
                                            <div class="truncate w-56">
                                                {{ selectedEventType?.name }}
                                            </div>
                                        </div>
                                        <IconChevronDown stroke-width="1.5" class="h-5 w-5 text-primary" aria-hidden="true"/>
                                    </div>
                                </ListboxButton>

                                <transition leave-active-class="transition ease-in duration-100" leave-from-class="opacity-100" leave-to-class="opacity-0">
                                    <ListboxOptions class="absolute w-full rounded-lg z-10 bg-primary shadow-lg max-h-32 pr-2 pt-2 pb-2 text-base ring-1 ring-black ring-opacity-5 overflow-y-scroll focus:outline-none sm:text-sm">
                                        <ListboxOption as="template" class="max-h-8"
                                                       v-for="type in usePage().props.eventTypes"
                                                       :key="type.name"
                                                       :value="type"
                                                       v-slot="{ active, selected }">
                                            <li :class="[active ? ' text-white' : 'text-secondary', 'group rounded-lg hover:bg-artwork-buttons-create cursor-pointer flex justify-between items-center py-2 pl-3 pr-9 text-sm subpixel-antialiased']">
                                                <div class="flex items-center">
                                                    <div>
                                                        <div class="block size-4 rounded-full"
                                                             :style="{'backgroundColor' : type?.hex_code }"/>
                                                    </div>
                                                    <span :class="[selected ? 'xsWhiteBold' : 'font-normal', 'ml-4 block truncate w-52']">
                                                    {{ type.name }}
                                                </span>
                                                </div>
                                                <span :class="[active ? ' text-white' : 'text-secondary', ' group flex justify-end items-center text-sm subpixel-antialiased']">
                                                      <IconCheck stroke-width="1.5" v-if="selected"
                                                                 class="h-5 w-5 flex text-success"
                                                                 aria-hidden="true"/>
                                                </span>
                                            </li>
                                        </ListboxOption>
                                    </ListboxOptions>
                                </transition>
                            </Listbox>
                        </div>
                        <div>
                            <TextInputComponent
                                id="name"
                                v-model="eventForm.eventName"
                                :label="$t('Event name')"
                            />
                        </div>
                        <div v-if="usePage().props.event_status_module" class="col-span-full">
                            <Listbox as="div" class="-mt-1 relative" v-model="selectedEventStatus" id="selectedEventStatus">
                                <ListboxLabel class="xsLight mb-2">{{ $t('Event Status') }}</ListboxLabel>
                                <ListboxButton class="menu-button">
                                    <div class="flex w-full justify-between">
                                        <div class="flex items-center gap-x-2">
                                            <div>
                                                <div class="block w-5 h-5 rounded-full" :style="{'backgroundColor' : selectedEventStatus?.color }"/>
                                            </div>
                                            <div class="truncate w-56">
                                                {{ selectedEventStatus?.name }}
                                            </div>
                                        </div>
                                        <IconChevronDown stroke-width="1.5" class="h-5 w-5 text-primary" aria-hidden="true"/>
                                    </div>
                                </ListboxButton>

                                <transition leave-active-class="transition ease-in duration-100" leave-from-class="opacity-100" leave-to-class="opacity-0">
                                    <ListboxOptions class="absolute w-full rounded-lg z-10 bg-primary shadow-lg max-h-32 pr-2 pt-2 pb-2 text-base ring-1 ring-black ring-opacity-5 overflow-y-scroll focus:outline-none sm:text-sm">
                                        <ListboxOption as="template" class="max-h-8"
                                                       v-for="status in usePage().props.eventStatuses"
                                                       :key="status.name"
                                                       :value="status"
                                                       v-slot="{ active, selected }">
                                            <li :class="[active ? ' text-white' : 'text-secondary', 'group rounded-lg hover:bg-artwork-buttons-create cursor-pointer flex justify-between items-center py-2 pl-3 pr-9 text-sm subpixel-antialiased']">
                                                <div class="flex items-center">
                                                    <div>
                                                        <div class="block size-4 rounded-full"
                                                             :style="{'backgroundColor' : status?.color }"/>
                                                    </div>
                                                    <span :class="[selected ? 'xsWhiteBold' : 'font-normal', 'ml-4 block truncate w-52']">
                                                     {{ status.name }}
                                                    </span>
                                                </div>
                                                <span :class="[active ? ' text-white' : 'text-secondary', ' group flex justify-end items-center text-sm subpixel-antialiased']">
                                                      <IconCheck stroke-width="1.5" v-if="selected"
                                                                 class="h-5 w-5 flex text-success"
                                                                 aria-hidden="true"/>
                                                </span>
                                            </li>
                                        </ListboxOption>
                                    </ListboxOptions>
                                </transition>
                            </Listbox>
                        </div>
                    </div>

                    <!-- time Block -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="col-span-full">
                            <div class="flex items-center gap-x-2">
                                <Switch v-model="eventForm.allDay" :class="[eventForm.allDay ? 'bg-artwork-buttons-create' : 'bg-gray-200', 'relative inline-flex h-6 w-10 shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-artwork-buttons-create focus:ring-offset-2']">
                                    <span :class="[eventForm.allDay ? 'translate-x-4' : 'translate-x-0', 'pointer-events-none relative inline-block size-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out']">
                                      <span :class="[eventForm.allDay ? 'opacity-0 duration-100 ease-out' : 'opacity-100 duration-200 ease-in', 'absolute inset-0 flex size-full items-center justify-center transition-opacity']" aria-hidden="true">
                                        <svg class="size-4 text-gray-400" fill="none" viewBox="0 0 12 12">
                                          <path d="M4 8l2-2m0 0l2-2M6 6L4 4m2 2l2 2" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                        </svg>
                                      </span>
                                      <span :class="[eventForm.allDay ? 'opacity-100 duration-200 ease-in' : 'opacity-0 duration-100 ease-out', 'absolute inset-0 flex size-full items-center justify-center transition-opacity']" aria-hidden="true">
                                        <svg class="size-4 text-artwork-buttons-create" fill="currentColor" viewBox="0 0 12 12">
                                          <path d="M3.707 5.293a1 1 0 00-1.414 1.414l1.414-1.414zM5 8l-.707.707a1 1 0 001.414 0L5 8zm4.707-3.293a1 1 0 00-1.414-1.414l1.414 1.414zm-7.414 2l2 2 1.414-1.414-2-2-1.414 1.414zm3.414 2l4-4-1.414-1.414-4 4 1.414 1.414z" />
                                        </svg>
                                      </span>
                                    </span>
                                </Switch>
                                <div>
                                    <p class="xsDark">{{ $t('Full day')}}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-x-4 pt-3">
                        <div>
                            <div class="w-full flex">
                                <DateInputComponent
                                    id="startDate"
                                    v-model="eventForm.startDate"
                                    label="Start"
                                    :classes="!eventForm.allDay ? '!rounded-l-lg !rounded-r-none' : '!rounded-lg'"
                                />

                                <TimeInputComponent
                                    v-model="eventForm.startTime"
                                    id="changeStartTime"
                                    v-if="!eventForm.allDay"
                                    label="Startzeit"
                                    classes="!rounded-r-lg !rounded-l-none border-l-0"
                                    required
                                />
                            </div>
                        </div>
                        <div>
                            <div class="w-full flex">
                                <DateInputComponent
                                    v-model="eventForm.endDate"
                                    id="endDate"
                                    label="End"
                                    :classes="!eventForm.allDay ? '!rounded-l-lg !rounded-r-none' : '!rounded-lg'"
                                />
                                <TimeInputComponent
                                    v-model="eventForm.endTime"
                                    v-if="!eventForm.allDay"
                                    id="changeEndTime"
                                    label="Endzeit"
                                    classes="!rounded-r-lg !rounded-l-none border-l-0"
                                    required
                                />
                            </div>
                        </div>
                    </div>
                </div>

                <div class="px-6 py-2">
                    <div v-if="!eventToEdit">
                        <SwitchGroup as="div" class="flex items-center mt-3 mb-1">
                            <Switch v-model="eventForm.is_series"
                                    :class="[eventForm.is_series ? 'bg-indigo-600 cursor-pointer' : 'bg-gray-200', 'relative inline-flex h-3 w-8 flex-shrink-0 rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-1 focus:ring-indigo-600 focus:ring-offset-2']">
                            <span aria-hidden="true"
                                  :class="[eventForm.is_series ? 'translate-x-5' : 'translate-x-0', 'pointer-events-none inline-block h-2 w-2 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out']"/>
                            </Switch>
                            <SwitchLabel as="span" class="ml-3 text-sm">
                            <span :class="[eventForm.is_series ? 'xsDark' : 'xsLight', 'text-sm']">
                                {{ $t('Repeat event') }}
                            </span>
                            </SwitchLabel>
                        </SwitchGroup>
                        <div v-if="eventForm.is_series">
                            <div class="grid grid-cols-2 gap-2">
                                <Listbox :disabled="eventToEdit?.is_series" as="div" v-model="selectedFrequency">
                                    <div class="relative mt-5">
                                        <ListboxButton
                                            class="menu-button">
                                            <div class="block truncate">{{ selectedFrequency.name }}</div>
                                            <span
                                                class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-2">
                                             <IconChevronDown stroke-width="1.5" class="h-5 w-5 text-primary"
                                                              aria-hidden="true"/>
                                        </span>
                                        </ListboxButton>

                                        <transition leave-active-class="transition ease-in duration-100"
                                                    leave-from-class="opacity-100" leave-to-class="opacity-0">
                                            <ListboxOptions
                                                class="absolute z-50 mt-1 max-h-28 w-full overflow-auto rounded-md bg-white py-1 text-base shadow-lg ring-1 ring-black ring-opacity-5 focus:outline-none sm:text-sm">
                                                <ListboxOption as="template" v-for="frequency in frequencies"
                                                               :key="frequency.id" :value="frequency"
                                                               v-slot="{ active, selected }">
                                                    <li :class="[active ? 'bg-indigo-600 text-white' : 'text-gray-900', 'relative cursor-default select-none py-2 pl-3 pr-9']">
                                                    <span
                                                        :class="[selected ? 'font-semibold' : 'font-normal', 'block truncate']">{{
                                                            frequency.name
                                                        }}</span>

                                                        <span v-if="selected"
                                                              :class="[active ? 'text-white' : 'text-indigo-600', 'absolute inset-y-0 right-0 flex items-center pr-4']">
                                                        <IconCheck stroke-width="1.5" class="h-5 w-5"
                                                                   aria-hidden="true"/>
                                                    </span>
                                                    </li>
                                                </ListboxOption>
                                            </ListboxOptions>
                                        </transition>
                                    </div>
                                </Listbox>
                                <div>
                                    <div class="w-full flex">
                                        <DateInputComponent
                                            :disabled="eventToEdit?.is_series"
                                            v-model="eventForm.seriesEndDate"
                                            id="endDate"
                                            :label="$t('End date Repeat event')"
                                        />
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div v-else-if="eventToEdit?.is_series" class="xsLight mt-2">{{ $t('Event is part of a repeat event') }}</div>
                    <div v-if="eventToEdit?.is_series" class="xsLight mb-2">
                        {{ $t('Cycle: {0} to {1}', [selectedFrequency.name, convertDateFormat(seriesEndDate)]) }}
                    </div>
                </div>

                <div class="px-6 py-2">
                    <Listbox as="div" class="relative" v-model="selectedRoom" id="room">
                        <ListboxButton class="menu-button" @click="checkCollisions">
                        <span v-if="!selectedRoom">
                            {{ $t('Select room') }}*
                        </span>
                            <div class="flex-grow flex text-left xsDark" v-else>
                                {{ selectedRoom?.name }}
                            </div>
                            <IconChevronDown stroke-width="1.5" class="h-5 w-5 text-primary" aria-hidden="true"/>
                        </ListboxButton>
                        <ListboxOptions class="w-full rounded-lg bg-primary max-h-32 overflow-y-auto text-sm absolute z-30">
                            <ListboxOption v-for="room in rooms"
                                           class="hover:bg-indigo-800 text-secondary cursor-pointer p-2 flex justify-between"
                                           :key="room.name"
                                           :value="room"
                                           v-slot="{ active, selected }">
                                <div :class="[selected ? 'xsWhiteBold' : 'xsLight', 'flex']">
                                    {{ room.name }}
                                    <IconAlertTriangle stroke-width="1.5" class="h-4 w-4 mx-2" aria-hidden="true" v-if="roomCollisionArray[room.id] > 0"/>
                                </div>
                                <IconCheck stroke-width="1.5" v-if="selected" class="h-5 w-5 text-success" aria-hidden="true"/>
                            </ListboxOption>
                        </ListboxOptions>
                    </Listbox>
                </div>

                <div class="px-6 py-2 bg-gray-100 my-5">
                    <div class="my-3">
                        <input type="checkbox" v-model="showProjectInfo" class="input-checklist">
                        <span :class="[showProjectInfo ? 'xsDark' : 'xsLight', 'text-sm ml-2']">{{ $t('Assign event to a project') }}</span>
                    </div>
                   <div v-if="showProjectInfo">
                       <div class="xsLight flex" v-if="!creatingProject">
                           {{ $t('Currently assigned to:') }}
                           <a v-if="selectedProject?.id"
                              :href="route('projects.tab', {project: selectedProject.id, projectTab: 1})"
                              class="ml-3 flex xsDark">
                               {{ selectedProject?.name }}
                           </a>
                           <div v-else class="xsDark ml-2">
                               {{ selectedProject?.name ?? 'Keinem Projekt' }}
                           </div>
                           <div v-if="selectedProject?.id"
                                class="flex items-center my-auto">
                               <button type="button"
                                       @click="selectedProject = null">
                                   <IconCircleX stroke-width="1.5"
                                                class="pl-2 h-6 w-6 hover:text-error text-primary"/>
                               </button>
                           </div>
                       </div>
                       <div class="xsLight" v-if="creatingProject">
                           {{ $t('The project is created when it is saved.') }}
                       </div>

                       <div class="my-2">
                           <div class="flex pb-2">
                               <SwitchGroup as="div" class="flex items-center">
                                   <SwitchLabel as="span" class="mr-3 text-sm" :class="creatingProject ? 'font-bold' : 'text-gray-400'">
                                       {{ $t('New project') }}
                                   </SwitchLabel>
                                   <Switch v-model="creatingProject" :class="[creatingProject ? 'bg-artwork-buttons-create' : 'bg-artwork-buttons-create', 'relative inline-flex h-3 w-6 flex-shrink-0 rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none']">
                                       <span aria-hidden="true" :class="[!creatingProject  ? 'translate-x-3' : 'translate-x-0', 'pointer-events-none inline-block h-2 w-2 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out']" />
                                   </Switch>
                                   <SwitchLabel as="span" class="ml-3 text-sm" :class="!creatingProject? 'font-bold' : 'text-gray-400'">
                                       {{ $t('Existing project') }}
                                   </SwitchLabel>
                               </SwitchGroup>
                           </div>
                           <div class="relative w-full">
                               <TextInputComponent
                                   id="projectName"
                                   :label="creatingProject ? $t('New project name') : $t('Search project')"
                                   v-model="projectName"
                               />
                               <div v-if="projectSearchResults.length > 0 && !creatingProject"
                                    class="absolute bg-primary truncate sm:text-sm w-full z-10">
                                   <div v-for="(project, index) in projectSearchResults"
                                        :key="index"
                                        @click="chooseProject(project)"
                                        class="p-4 xsWhiteBold border-l-4 hover:border-l-success border-l-primary cursor-pointer">
                                       {{ project.name }}
                                   </div>
                               </div>
                           </div>

                       </div>
                   </div>
                    <!--<div v-if="projectGroupProjects.length > 0" class="mt-3 mb-4 flex items-center flex-wrap gap-3">
                        <div v-for="(groupProject, index) in projectGroupProjects" class="group block shrink-0 bg-gray-50 w-fit pr-3 rounded-full border border-gray-300">
                            <div class="flex items-center">
                                <div>
                                    <img class="inline-block size-9 rounded-full object-cover" :src="groupProject?.key_visual_path ? '/storage/keyVisual/' + groupProject?.key_visual_path : '/storage/logo/artwork_logo_small.svg'" alt="" />
                                </div>
                                <div class="mx-2">
                                    <p class="xsDark group-hover:text-gray-900">{{ groupProject.name}}</p>
                                </div>
                                <div class="flex items-center">
                                    <button type="button" @click="deleteProjectFromProjectGroup(index)">
                                        <XIcon class="h-4 w-4 text-gray-400 hover:text-error" />
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>-->
                </div>

                <pre>
                    {{selectedProject}}
                </pre>
            </div>
        </form>
    </BaseModal>
</template>

<script setup>

import BaseModal from "@/Components/Modals/BaseModal.vue";
import ModalHeader from "@/Components/Modals/ModalHeader.vue";
import UserPopoverTooltip from "@/Layouts/Components/UserPopoverTooltip.vue";
import {useForm, usePage} from "@inertiajs/vue3";
import {onMounted, ref} from "vue";
import TextInputComponent from "@/Components/Inputs/TextInputComponent.vue";
import {IconAlertTriangle, IconCheck, IconChevronDown, IconCircleX} from "@tabler/icons-vue";
import {
    Listbox,
    ListboxButton,
    ListboxLabel,
    ListboxOption,
    ListboxOptions,
    Switch, SwitchGroup, SwitchLabel,
} from "@headlessui/vue";
import TimeInputComponent from "@/Components/Inputs/TimeInputComponent.vue";
import DateInputComponent from "@/Components/Inputs/DateInputComponent.vue";
import ProjectSearch from "@/Components/SearchBars/ProjectSearch.vue";
import Input from "@/Jetstream/Input.vue";
import Button from "@/Jetstream/Button.vue";

const props = defineProps({
    eventToEdit: {
        type: Object,
        required: false,
        default: null
    },
    rooms: {
        type: Object,
        required: true
    },
    roomCollisions: {
        type: Object,
        required: true,
        default: [],
    }
})

const modalIsLoading = ref(false);

const eventForm = useForm({
    id: props.eventToEdit ? props.eventToEdit.id : null,
    eventName : props.eventToEdit ? props.eventToEdit.eventName : '',
    description: props.eventToEdit ? props.eventToEdit.description : '',
    eventTypeId: props.eventToEdit ? props.eventToEdit.projectId : null,
    eventStatusId: props.eventToEdit ? props.eventToEdit.eventStatusId : null,
    allDay: props.eventToEdit ? props.eventToEdit.allDay : false,
    startTime: props.eventToEdit ? props.eventToEdit.start.split(' ')[1] : null,
    endTime:  props.eventToEdit ? props.eventToEdit.end.split(' ')[1] : null,
    startDate:  props.eventToEdit ? props.eventToEdit.start.split(' ')[0] : null,
    endDate:  props.eventToEdit ? props.eventToEdit.end.split(' ')[0] : null,
    is_series: props.eventToEdit ? props.eventToEdit?.is_series : false,
    seriesEndDate: props.eventToEdit ? props.eventToEdit.seriesEndDate : null,
    roomId: props.eventToEdit ? props.eventToEdit.roomId : null,
    projectId: props.eventToEdit ? props.eventToEdit?.project.id : null,
});

const frequencies = ref( [
    {
        id: 1,
        name: 'Täglich'
    },
    {
        id: 2,
        name: 'Wöchentlich'
    },
    {
        id: 3,
        name: 'Alle 2 Wochen'
    },
    {
        id: 4,
        name: 'Monatlich'
    }
]);

const selectedEventType = ref(null);
const selectedEventStatus = ref(null);
const selectedFrequency = ref(frequencies.value[0]);
const selectedRoom = ref(props.eventToEdit ? props.rooms.find(room =>
    room.id === props.eventToEdit.roomId
) : null);
const roomCollisionArray = ref(props.roomCollisions);
const selectedProject = ref(props.eventToEdit ? props.eventToEdit.project : null);
const projectSearchQuery = ref('');
const showProjectInfo = ref(false);
const creatingProject = ref(true);
const projectName = ref('');
const projectSearchResults = ref([]);

onMounted(() => {
    selectedEventType.value = props.eventToEdit ? usePage().props.eventTypes.find(type =>
        type.id === props.eventToEdit.eventTypeId
    ) : usePage().props.eventTypes[0];

    selectedEventStatus.value = props.eventToEdit ? usePage().props.eventStatuses.find(status =>
        status.id === props.eventToEdit.eventStatusId
    ) : usePage().props.eventStatuses.find(status => status.default);
})
const checkCollisions = async() => {
    if (
        eventForm.startTime && eventForm.startDate && eventForm.endTime && eventForm.endDate ||
        eventForm.allDay && eventForm.startDate && eventForm.endDate
    ) {
        let startFull = formatDate(eventForm.startDate, eventForm.startTime ?? '00:00');
        let endFull = formatDate(eventForm.endDate, eventForm.endTime ?? '23:59');
        await axios.post('/collision/room', {
            params: {
                start: startFull,
                end: endFull
            }
        }).then(response => roomCollisionArray.value = response.data);
    }
}

const formatDate = (date, time) => {
    if (date === null || time === null) return null;
    return (new Date(date + ' ' + time)).toISOString()
}

const UpdateOrCreateEvent = () => {

}

const emits = defineEmits(['closed'])


</script>

<style scoped>

</style>