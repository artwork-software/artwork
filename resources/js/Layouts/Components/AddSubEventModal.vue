<template>
    <BaseModal @closed="closeModal(false)" v-if="show" modal-image="/Svgs/Overlays/illu_appointment_new.svg">
        <div class="mx-4">
            <ModalHeader
                :title="subEventToEdit ? $t('Edit sub-event') : $t('New sub-event')"
                :description="$t('Please note that the appointment must take place within the appointment group period.')"
                :sub-title="$t('Belongs to Blocker in {0}', [event.roomName])"
            />
            <div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-x-4 mb-4">
                    <div>
                        <Listbox as="div" class="flex mt-5 relative" v-model="subEvent.selectedEventType" id="eventType">
                            <ListboxButton class="menu-button">
                                <div class="flex items-center">
                                    <div class="block w-5 h-5 rounded-full" :style="{'backgroundColor' : subEvent.selectedEventType?.hex_code }">
                                    </div>
                                    <span class="flex truncate items-center ml-3">
                                            <span>{{ subEvent.selectedEventType?.name }}</span>
                                    </span>
                                    <span class="ml-2 right-0 absolute inset-y-0 flex items-center pr-2 pointer-events-none">
                                        <IconChevronDown stroke-width="1.5" class="h-5 w-5 text-primary" aria-hidden="true"/>
                                    </span>
                                </div>
                            </ListboxButton>

                            <transition leave-active-class="transition ease-in duration-100" leave-from-class="opacity-100" leave-to-class="opacity-0">
                                <ListboxOptions class="absolute w-full z-10 mt-12 bg-primary shadow-lg max-h-32 pr-2 pt-2 pb-2 text-base ring-1 ring-black ring-opacity-5 overflow-y-scroll focus:outline-none sm:text-sm">
                                    <ListboxOption as="template" class="max-h-8"
                                                   v-for="eventType in filteredEventTypes"
                                                   :key="eventType.name"
                                                   :value="eventType"
                                                   v-slot="{ active, selected }">
                                        <li :class="[active ? ' text-white' : 'text-secondary', 'group hover:border-l-4 hover:border-l-success cursor-pointer flex justify-between items-center py-2 pl-3 pr-9 text-sm subpixel-antialiased']">
                                            <div class="flex">
                                                <div>
                                                    <div class="block w-3 h-3 rounded-full"
                                                         :style="{'backgroundColor' : eventType?.hex_code }"/>
                                                </div>
                                                <span
                                                    :class="[selected ? 'xsWhiteBold' : 'font-normal', 'ml-4 block truncate']">{{
                                                        eventType.name
                                                    }}
                                                    </span>
                                            </div>
                                            <span
                                                :class="[active ? ' text-white' : 'text-secondary', ' group flex justify-end items-center text-sm subpixel-antialiased']">
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

                    <div class="">
                        <TextInputComponent
                            v-model="subEvent.eventName"
                            @keyup="subEvent.eventName.length > 0 ? submit = true : subEvent.selectedEventType?.individual_name ? submit = false : submit = true"
                            id="eventTitle"
                            :label="subEvent.selectedEventType?.individual_name ? 'Terminname*' : 'Terminname'"
                        />

                    </div>
                </div>
                <!-- Attribute Menu -->
                <Menu as="div" class="inline-block text-left w-full relative">
                    <div>
                        <MenuButton class="menu-button">
                            <span class="float-left flex xsLight subpixel-antialiased">
                                <img src="/Svgs/IconSvgs/icon_adjustments.svg" class="mr-2" alt="attributeIcon"/>
                                {{ $t('Select appointment properties') }}
                            </span>
                            <IconChevronDown stroke-width="1.5" class="ml-2 -mr-1 h-5 w-5 text-primary float-right" aria-hidden="true"/>
                        </MenuButton>
                    </div>
                    <transition
                        enter-active-class="transition duration-50 ease-out"
                        enter-from-class="transform scale-100 opacity-100"
                        enter-to-class="transform scale-100 opacity-100"
                        leave-active-class="transition duration-75 ease-in"
                        leave-from-class="transform scale-100 opacity-100"
                        leave-to-class="transform scale-95 opacity-0"
                    >
                        <MenuItems
                            class="absolute overflow-y-auto h-44 w-full rounded-lg origin-top-left divide-y divide-gray-200 bg-primary ring-1 ring-black p-2 text-white opacity-100 z-50">
                            <div class="mx-auto w-full rounded-2xl bg-primary border-none mt-2">
                                <div class="w-full rounded-2xl bg-primary border-none mt-2 flex flex-col gap-y-2">
                                    <div v-for="eventProperty in this.event_properties" class="flex flex-row gap-x-1 w-full items-center">
                                        <input v-model="eventProperty.checked"
                                               type="checkbox"
                                               class="input-checklist-dark"/>
                                        <component :is="eventProperty.icon" class="w-5 h-5 text-white" stroke-width="2"/>
                                        <div :class="[eventProperty.checked ? 'xsWhiteBold' : 'xsLight', 'my-auto']">
                                            {{ eventProperty.name }}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </MenuItems>
                    </transition>
                </Menu>

                <div v-if="subEvent?.eventProperties.length > 0" class="mt-3 mb-4 flex items-center flex-wrap gap-2">
                    <div v-for="(eventProperty, index) in subEvent?.eventProperties" class="group block shrink-0 bg-gray-50 w-fit pr-3 rounded-full border border-gray-300">
                        <div class="flex items-center">
                            <div class="rounded-full p-1 size-8 flex items-center justify-center">
                                <component :is="eventProperty.icon" class="inline-block size-4"  />
                            </div>
                            <div class="mx-1">
                                <p class="xxsDark group-hover:text-gray-900">{{ eventProperty.name}}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!--    Properties    -->
                <div class="flex py-2">
                    <div v-if="subEvent.audience">
                        <TagComponent icon="audience" :displayed-text="$t('With audience')" hideX="true" property=""/>
                    </div>
                    <div v-if="subEvent.is_loud">
                        <TagComponent :displayed-text="$t('It gets loud')" hideX="true" property=""/>
                    </div>
                </div>
                <div class="w-full">
                    <SwitchGroup as="div" class="flex items-center">
                        <Switch v-model="this.allDayEvent"
                                :class="[this.allDayEvent ? 'bg-indigo-600' : 'bg-gray-200', 'relative inline-flex h-3 w-8 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-1 focus:ring-indigo-600 focus:ring-offset-2']">
                            <span aria-hidden="true"
                                  :class="[this.allDayEvent ? 'translate-x-5' : 'translate-x-0', 'pointer-events-none inline-block h-2 w-2 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out']"/>
                        </Switch>
                        <SwitchLabel as="span" class="ml-3 text-sm">
                            <span :class="[this.allDayEvent ? 'xsDark' : 'xsLight', 'text-sm']">
                                {{ $t('Full day') }}
                            </span>
                        </SwitchLabel>
                    </SwitchGroup>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-x-4 pt-3">
                    <div>
                        <div class="w-full flex">
                            <DateInputComponent
                                id="startDate"
                                @change="checkTimes()"
                                v-model="startDate"
                                label="Start"
                                disabled
                                :classes="!allDayEvent ? '!rounded-l-lg !rounded-r-none border-r-0' : '!rounded-lg'"
                            />

                            <TimeInputComponent
                                v-model="startTime"
                                id="changeStartTime"
                                v-if="!allDayEvent"
                                @change="checkTimes()"
                                label="Startzeit"
                                classes="!rounded-r-lg !rounded-l-none"
                                required
                            />
                        </div>
                    </div>
                    <div>
                        <div class="w-full flex">
                            <DateInputComponent
                                v-model="endDate"
                                id="endDate"
                                @change="checkTimes()"
                                label="End"
                                disabled
                                :classes="!allDayEvent ? '!rounded-l-lg !rounded-r-none border-r-0' : '!rounded-lg'"
                            />
                            <TimeInputComponent
                                v-model="endTime"
                                v-if="!allDayEvent"
                                id="changeEndTime"
                                @change="checkTimes()"
                                label="Endzeit"
                                classes="!rounded-r-lg !rounded-l-none"
                                required
                            />
                        </div>
                    </div>

                </div>
                <div>
                    <div class="text-red-500 text-xs" v-show="helpText.length > 0">{{ helpText }}</div>
                    <div class="text-red-500 text-xs" v-show="helpTextStart.length > 0">{{
                            helpTextStart
                        }}
                    </div>
                    <div class="text-red-500 text-xs" v-show="helpTextEnd.length > 0">{{
                            helpTextEnd
                        }}
                    </div>
                    <div class="text-red-500 text-xs" v-show="helpTextLength.length > 0">{{
                            helpTextLength
                        }}
                    </div>
                </div>


                <div class="pt-4">
                    <TextareaComponent
                        :label="$t('What do I need to bear in mind for the event?')"
                        id="description"
                        v-model="subEvent.description"
                        rows="4"
                    />
                </div>
            </div>
        </div>
        <div class="flex justify-center w-full py-4">
            <FormButton
                :disabled="!submit"
                @click="updateOrCreateEvent()"
                :text="$t('Vouchers')"
            />
        </div>
    </BaseModal>
</template>

<script>
import JetDialogModal from "@/Jetstream/DialogModal.vue";
import {XIcon} from "@heroicons/vue/outline";
import {CheckIcon, ChevronDownIcon} from "@heroicons/vue/solid";
import {
    Listbox,
    ListboxButton,
    ListboxLabel,
    ListboxOption,
    ListboxOptions,
    Menu,
    MenuButton,
    MenuItem,
    MenuItems,
    Switch,
    SwitchGroup,
    SwitchLabel
} from "@headlessui/vue";
import {useForm} from "@inertiajs/vue3";
import TagComponent from "@/Layouts/Components/TagComponent.vue";
import dayjs from "dayjs";
import Permissions from "@/Mixins/Permissions.vue";
import Input from "@/Jetstream/Input.vue";
import FormButton from "@/Layouts/Components/General/Buttons/FormButton.vue";
import IconLib from "@/Mixins/IconLib.vue";
import BaseModal from "@/Components/Modals/BaseModal.vue";
import {useEvent} from "@/Composeables/Event.js";
import TextInputComponent from "@/Components/Inputs/TextInputComponent.vue";
import ModalHeader from "@/Components/Modals/ModalHeader.vue";
import DateInputComponent from "@/Components/Inputs/DateInputComponent.vue";
import TimeInputComponent from "@/Components/Inputs/TimeInputComponent.vue";
import TextareaComponent from "@/Components/Inputs/TextareaComponent.vue";
import {inject, nextTick} from "vue";

const {getDaysOfEvent, formatEventDateByDayJs} = useEvent();
export default {
    name: "AddSubEventModal",
    mixins: [Permissions, IconLib],
    computed: {
        dayjs() {
            const utc = require('dayjs/plugin/utc');
            dayjs.extend(utc)
            return dayjs
        },
        filteredEventTypes() {
            return this.eventTypes.filter(eventType => eventType.id !== 1)
        },

    },
    setup() {
        const event_properties = inject('event_properties');
        return {event_properties}
    },
    components: {
        TextareaComponent,
        TimeInputComponent, DateInputComponent,
        ModalHeader,
        TextInputComponent,
        BaseModal,
        FormButton,
        SwitchLabel,
        Switch,
        SwitchGroup,
        Input,
        TagComponent,
        JetDialogModal,
        XIcon,
        ChevronDownIcon,
        Listbox, ListboxLabel, ListboxButton, ListboxOption, ListboxOptions,
        Menu, MenuItem, MenuItems, MenuButton, CheckIcon
    },
    data() {
        return {
            subEvent: useForm({
                event_id: this.event.id,
                eventName: this.subEventToEdit?.eventName ? this.subEventToEdit?.eventName : '',
                selectedEventType: this.subEventToEdit?.type ? this.subEventToEdit?.type : this.eventTypes[1],
                start_time: this.subEventToEdit?.start ? this.subEventToEdit?.start : dayjs(this.event.start).format('YYYY-MM-DD HH:mm'),
                end_time: this.subEventToEdit?.end ? this.subEventToEdit?.end : dayjs(this.event.end).format('YYYY-MM-DD HH:mm'),
                description: this.subEventToEdit?.description ? this.subEventToEdit?.description : '',
                user_id: this.$page.props.auth.user.id,
                event_type_id: this.subEventToEdit?.eventTypeId ? this.subEventToEdit?.eventTypeId : '',
                allDay: this.subEventToEdit?.allDay ? this.subEventToEdit?.allDay : false,
                eventProperties: this.subEventToEdit?.event_properties ? this.subEventToEdit?.event_properties : [],
            }),
            edit: !!this.subEventToEdit?.id,
            helpText: '',
            helpTextStart: '',
            helpTextEnd: '',
            helpTextLength: '',
            allDayEvent: this.subEventToEdit?.allDay ? this.subEventToEdit.allDay : false,
            startTime: this.subEventToEdit?.start ? dayjs(this.subEventToEdit?.start).format('HH:mm') : dayjs(this.event.start).format('HH:mm'),
            endTime: this.subEventToEdit?.end ? dayjs(this.subEventToEdit?.end).format('HH:mm') : dayjs(this.event.end).format('HH:mm'),
            startDate: this.subEventToEdit?.start ? dayjs(this.subEventToEdit?.start).format('YYYY-MM-DD') : dayjs(this.event.start).format('YYYY-MM-DD'),
            endDate: this.subEventToEdit?.end ? dayjs(this.subEventToEdit?.end).format('YYYY-MM-DD') : dayjs(this.event.end).format('YYYY-MM-DD'),
            show: true,
            submit: this.subEventToEdit?.eventType ? this.subEventToEdit?.eventType.individual_name ? this.subEventToEdit?.title?.length > 0 : true : true,
            //event_properties: event_properties
        }
    },
    props: ['event', 'eventTypes', 'subEventToEdit'],
    emits: ['close'],
    mounted() {
       nextTick(() => {

       })
    },
    methods: {
        closeModal(bool, desiredRoomIds, desiredDays) {
            this.$emit('close', bool, desiredRoomIds, desiredDays);
        },
        formatDate(date, time) {
            if (date === null || time === null) return null;
            return (new Date(date + ' ' + time)).toISOString()
        },
        checkTimes() {
            this.submit = true;
            this.subEvent.allDay = this.allDayEvent;
            if (this.allDayEvent) {
                this.handleAllDayEventChange()
            }
            this.subEvent.start_time = dayjs(this.formatDate(this.startDate, this.startTime)).format('YYYY-MM-DD HH:mm');
            this.subEvent.end_time = dayjs(this.formatDate(this.endDate, this.endTime)).format('YYYY-MM-DD HH:mm');
            if (this.subEvent.start_time > this.subEvent.end_time && this.subEvent.end_time && this.subEvent.start_time) {
                this.helpText = this.$t('The end time cannot be before the start time!');
                this.submit = false;
            } else {
                this.helpText = '';
            }

            // check if event min 30min
            if (this.subEvent.start_time && this.subEvent.end_time) {
                const date = new Date(this.subEvent.start_time);
                const minimumEnd = this.addMinutes(date, 30);
                if (minimumEnd <= new Date(this.subEvent.end_time)) {
                    this.helpTextLength = '';
                } else {
                    this.helpTextLength = this.$t('The event must not be shorter than 30 minutes');
                    this.submit = false;
                }
            }

        },
        addMinutes(date, minutes) {
            date.setMinutes(date.getMinutes() + minutes);
            return date;
        },
        checkSubmitStatus() {
            if (this.subEvent.selectedEventType?.individual_name) {
                this.submit = this.subEvent.eventName?.length > 0;
            } else {
                this.submit = true;
            }
        },
        handleAllDayEventChange() {
            if (this.allDayEvent) {
                // Set startTime to "00:00" and endTime to "23:59" for all-day event
                this.startTime = "00:00";
                this.endTime = "23:59";
            } else {
                // Handle other logic if needed when allDayEvent is false
            }
        },
        updateOrCreateEvent() {
            const onSuccess = () => {
                this.closeModal(
                    true,
                    [this.event.roomId],
                    getDaysOfEvent(
                        //use main event date for reload, sub event can only be on same day
                        formatEventDateByDayJs(this.event.start),
                        formatEventDateByDayJs(this.event.end)
                    )
                );
            };

            this.subEvent.event_type_id = this.subEvent?.selectedEventType?.id;

            const payload = {
                event_id: this.subEvent.event_id,
                eventName: this.subEvent.eventName,
                selectedEventType: this.subEvent.selectedEventType,
                start_time: this.subEvent.start_time,
                end_time: this.subEvent.end_time,
                description:  this.subEvent.description,
                user_id: this.subEvent.user_id,
                event_type_id: this.subEvent.event_type_id,
                allDay: this.subEvent.allDay,
                eventProperties: this.event_properties
                    .filter((eventProperty) => eventProperty.checked)
                    .map((eventProperty) => eventProperty.id),
            };

            if (this.edit) {
                axios.patch(route('subEvent.update', this.subEventToEdit.id), payload).finally(() => onSuccess());
            } else {
                axios.post(route('subEvent.add'), payload).finally(() => onSuccess());
            }

        }
    },
    watch: {
        'subEvent.selectedEventType': {
            handler(newVal, oldVal) {
                this.checkSubmitStatus();
            },
            deep: true,
        },
        allDayEvent: {
            handler() {
                this.checkTimes()
            }
        },
        subEvent: {
            immediate: true,
            deep: true,
            handler(){
                this.event_properties?.forEach((event_property) => {
                    event_property.checked = this.subEventToEdit?.event_properties.some(
                        (event_event_properties) => event_event_properties.id === event_property.id
                    );
                });
            }
        }
    },
}
</script>

<style scoped>

</style>
