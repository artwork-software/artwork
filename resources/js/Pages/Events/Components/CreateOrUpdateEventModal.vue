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
                                <DateOrDateTimeInputComponent
                                    id="startDate"
                                    v-model="eventForm.startDate"
                                    label="Start"
                                    :type-string="eventForm.allDay ? 'date' : 'datetime-local'"
                                />
                            </div>
                        </div>
                        <div>
                            <div class="w-full flex">
                                <DateOrDateTimeInputComponent
                                    v-model="eventForm.endDate"
                                    id="endDate"
                                    label="End"
                                    :type-string="eventForm.allDay ? 'date' : 'datetime-local'"
                                />
                            </div>
                        </div>

                    </div>
                </div>

                <div class="px-6 py-2">
                </div>
            </div>
        </form>
    </BaseModal>
</template>

<script setup>

import BaseModal from "@/Components/Modals/BaseModal.vue";
import ModalHeader from "@/Components/Modals/ModalHeader.vue";
import UserPopoverTooltip from "@/Layouts/Components/UserPopoverTooltip.vue";
import {router, useForm, usePage} from "@inertiajs/vue3";
import {nextTick, onBeforeMount, ref, watch} from "vue";
import TextInputComponent from "@/Components/Inputs/TextInputComponent.vue";
import {IconCheck, IconChevronDown} from "@tabler/icons-vue";
import {
    Listbox,
    ListboxButton,
    ListboxLabel,
    ListboxOption,
    ListboxOptions,
    Switch,
} from "@headlessui/vue";
import DateOrDateTimeInputComponent from "@/Components/Inputs/DateOrDateTimeInputComponent.vue";

const props = defineProps({
    eventToEdit: {
        type: Object,
        required: false,
        default: null
    },
})

const modalIsLoading = ref(false);

const eventForm = useForm({
    id: props.eventToEdit ? props.eventToEdit.id : null,
    eventName : props.eventToEdit ? props.eventToEdit.eventName : '',
    description: props.eventToEdit ? props.eventToEdit.description : '',
    eventTypeId: props.eventToEdit ? props.eventToEdit.projectId : null,
    eventStatusId: props.eventToEdit ? props.eventToEdit.eventStatusId : null,
    allDay: props.eventToEdit ? props.eventToEdit.allDay : false,
    startDate: null,
    endDate: null,
});

const selectedEventType = ref(null);
const selectedEventStatus = ref(null);

onBeforeMount(() => {
    modalIsLoading.value = true;
    router.reload({
        only: ['eventTypes', 'eventStatuses'],
        onSuccess: () => {
            selectedEventType.value = props.eventToEdit ? usePage().props.eventTypes.find(type =>
                type.id === props.eventToEdit.projectId
            ) : usePage().props.eventTypes[0];

            selectedEventStatus.value = props.eventToEdit ? usePage().props.eventStatuses.find(status =>
                status.id === props.eventToEdit.eventStatusId
            ) : usePage().props.eventStatuses.find(status => status.default);
            setDatesIfEventIsEditing();
            modalIsLoading.value = false;
        }
    })
})

const UpdateOrCreateEvent = () => {

}

const emits = defineEmits(['closed'])

const setDatesIfEventIsEditing = () => {
    if (eventForm.allDay) {
        nextTick(() => {
            eventForm.startDate = props.eventToEdit.formatted_dates ? props.eventToEdit.formatted_dates.start_without_time : null;
            eventForm.endDate = props.eventToEdit.formatted_dates ? props.eventToEdit.formatted_dates.end_without_time : null;
        })
    } else {
        nextTick(() => {
            eventForm.startDate = props.eventToEdit.formatted_dates ? props.eventToEdit.formatted_dates.start_with_time : null;
            eventForm.endDate = props.eventToEdit.formatted_dates ? props.eventToEdit.formatted_dates.end_with_time : null;
        })
    }
}
// add watch on eventForm.allDay
// if allDay is true, set the time to 00:00:00
// if allDay is false, set the time to 23:59:59
watch(() => eventForm.allDay, (newValue) => {
    setDatesIfEventIsEditing();
})

</script>

<style scoped>

</style>