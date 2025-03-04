<template>
    <BaseModal @closed="closeModal(false)" v-if="true" modal-image="/Svgs/Overlays/illu_appointment_warning.svg">
        <div class="mx-4">
            <ModalHeader
                :title="$t('Events without room')"
                :description="$t('These room booking requests have been rejected by the room admin. Cancel the appointments or move them to another room.')"
            />
            <div class="flex my-8 " v-for="event in this.computedEventsWithoutRoom">
                <SingleEventInEventsWithoutRoom
                    :computed-events-without-room="computedEventsWithoutRoom"
                    :first_project_calendar_tab_id="first_project_calendar_tab_id"
                    :event="event"
                    :event-types="eventTypes"
                    :rooms="rooms"
                    :isAdmin="isAdmin"
                    :remove-notification-on-action="removeNotificationOnAction"
                    :show-hints="showHints"
                    @desires-reload="this.requestReload"
                />
            </div>
        </div>
    </BaseModal>
</template>

<script>

import JetDialogModal from "@/Jetstream/DialogModal.vue";
import {ChevronDownIcon, DotsVerticalIcon, PencilAltIcon, XCircleIcon, XIcon} from '@heroicons/vue/outline';
import {
    Listbox,
    ListboxButton,
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
import {CheckIcon, ChevronUpIcon, TrashIcon} from "@heroicons/vue/solid";
import SvgCollection from "@/Layouts/Components/SvgCollection.vue";
import Input from "@/Jetstream/Input.vue";
import ConfirmationComponent from "@/Layouts/Components/ConfirmationComponent.vue";
import TagComponent from "@/Layouts/Components/TagComponent.vue";
import Permissions from "@/Mixins/Permissions.vue";
import {router} from "@inertiajs/vue3";
import IconLib from "@/Mixins/IconLib.vue";
import BaseModal from "@/Components/Modals/BaseModal.vue";
import SingleEventInEventsWithoutRoom from "@/Layouts/Components/SingleEventInEventsWithoutRoom.vue";
import ModalHeader from "@/Components/Modals/ModalHeader.vue";
import {provide, inject} from "vue";
provide('event_properties', inject('event_properties'));
export default {
    name: 'EventsWithoutRoomComponent',
    mixins: [Permissions, IconLib],
    components: {
        ModalHeader,
        SingleEventInEventsWithoutRoom,
        BaseModal,
        Switch,
        SwitchGroup,
        SwitchLabel,
        Input,
        JetDialogModal,
        XIcon,
        XCircleIcon,
        Listbox,
        ListboxButton,
        ListboxOption,
        ListboxOptions,
        ChevronDownIcon,
        ChevronUpIcon,
        SvgCollection,
        CheckIcon,
        Menu,
        MenuButton,
        MenuItem,
        MenuItems,
        PencilAltIcon,
        TrashIcon,
        DotsVerticalIcon,
        ConfirmationComponent,
        TagComponent
    },
    data() {
        return {
            startDate: null,
            startTime: null,
            endDate: null,
            endTime: null,
            isLoud: false,
            audience: false,
            projectName: null,
            title: null,
            eventName: null,
            eventTypeName: null,
            selectedEventType: this.eventTypes[0],
            selectedProject: null,
            selectedRoom: null,
            error: null,
            creatingProject: false,
            projectSearchResults: [],
            description: null,
            canEdit: false,
            deleteComponentVisible: false,
            eventToDelete: null,
            allDayEvent: false,
            showProjectInfo: false,
            firstCall: true,
            isOption: false,
            frequencies: [
                {
                    id: 1,
                    name: this.$t('Daily')
                },
                {
                    id: 2,
                    name: this.$t('Weekly')
                },
                {
                    id: 3,
                    name: this.$t('Every 2 weeks')
                },
                {
                    id: 4,
                    name: this.$t('Monthly')
                }
            ]
        }
    },
    props: [
        'showHints',
        'eventTypes',
        'rooms',
        'isAdmin',
        'eventsWithoutRoom',
        'removeNotificationOnAction',
        'first_project_calendar_tab_id'
    ],
    emits: ['closed', 'desiresReload'],
    watch: {
        projectName: {
            deep: true,
            handler() {
                if (this.creatingProject || !this.projectName) {
                    this.projectSearchResults = [];
                    return;
                }
                axios.get('/projects/search', {params: {query: this.projectName}})
                    .then(response => this.projectSearchResults = response.data)
            },
        }
    },
    computed: {
        computedEventsWithoutRoom: function () {
            this.eventsWithoutRoom.forEach((event) => {
                const dateStart = new Date(event.start);
                event.startDate = this.getDateOfDate(dateStart);
                event.startTime = this.getTimeOfDate(dateStart);

                const dateEnd = new Date(event.end);
                event.endDate = this.getDateOfDate(dateEnd);
                event.endTime = this.getTimeOfDate(dateEnd);

                event.creatingProject = false;
                //setting show project info for every event on first rendering
                if (this.firstCall) {
                    event.selectedFrequencyName = this.getFrequencyName(event.series?.frequency_id);
                }
            })
            this.firstCall = false;
            return this.eventsWithoutRoom;
        },
    },
    methods: {
        requestReload(desiredRoomIds, desiredDays, reloadEventsWithoutRoom) {
            //if opened by notification just close the modal because afterwards the notification
            //is deleted and the page reloads
            if (this.removeNotificationOnAction) {
                this.closeModal(true);
            }

            //this.$emit('desiresReload', desiredRoomIds, desiredDays, reloadEventsWithoutRoom);
        },
        getTimeOfDate(date) {
            //returns hours and minutes in format HH:mm, if necessary with leading zeros, from given date object
            return ('0' + date.getHours()).slice(-2) + ":" + ('0' + date.getMinutes()).slice(-2);
        },
        getDateOfDate(date) {
            //returns date in format "YYYY-MM-DD" from given date object, with leading zeros
            //make sure to add 1 to the returned month because javascript starts counting from 0, January = 0
            return date.getFullYear() + "-" +
                (date.getMonth() + 1).toString().padStart(2, '0') + '-' +
                date.getDate().toString().padStart(2, '0');
        },
        convertDateFormat(dateString) {
            const parts = dateString.split('-');
            return parts[2] + "." + parts[1] + "." +parts[0];
        },
        getFrequencyName(frequencyId) {
            const matchedFrequency = this.frequencies.find(frequency => frequency.id === frequencyId);

            if (matchedFrequency) {
                return matchedFrequency.name;
            } else {
                return this.$t('No cycle selected');
            }
        },
        onLinkingProject(project, event) {
            event.projectId = project.id;
            event.project = project;
            event.projectName = '';
            this.projectName = '';
            event.showProjectSearchResults = false;
            this.projectSearchResults = [];
        },
        closeModal(bool) {
            this.$emit('closed', bool);
        },
        /**
         * Format date and time to ISO 8601 with timezone UTC
         *
         * @param date
         * @param time
         * @returns {string|null}
         */
        formatDate(date, time) {
            if (date === null || time === null) return null;
            return (new Date(date + ' ' + time)).toISOString()
        },
        checkChanges(event) {
            this.updateTimes(event);
        },
        /**
         * If the user selects a start, end, and room
         * call the server to get information if there are any collision
         *
         * @returns {Promise<void>}
         */
        async checkCollisions(event) {
            if (
                event.startTime && event.startDate && event.endTime && event.endDate ||
                event.allDay && event.startDate && event.endDate
            ) {
                let startFull = this.formatDate(event.startDate, !event.allDay ? event.startTime : '00:00');
                let endFull = this.formatDate(event.endDate, !event.allDay ? event.endTime : '23:59');

                await axios.post('/collision/room', {
                    params: {
                        start: startFull,
                        end: endFull
                    }
                }).then(response => event.roomCollisionArray = response.data);
            }
        },
        updateTimes(event) {
            if (event.startDate) {
                if (!event.endDate && this.checkYear(event.startDate)) {
                  event.endDate = event.startDate;
                }
                if (event.startTime) {
                    if (!event.endTime) {
                          if (event.startTime === '23:00') {
                            event.endTime = '23:59';
                          } else {
                              let startHours = event.startTime.slice(0, 2);
                              if (startHours === '23') {
                                  event.endTime = '00:' + event.startTime.slice(3, 5);
                                  let date = new Date();
                                  event.endDate = new Date(
                                      date.setDate(new Date(event.endDate).getDate() + 1)
                                  ).toISOString().slice(0, 10);
                              } else {
                                  event.endTime = this.getNextHourString(event.startTime)
                              }
                        }
                    }
                }
            }

            this.validateStartBeforeEndTime(event);
            this.checkCollisions(event);
        },
        async validateStartBeforeEndTime(event) {
            event.error = null;
            if (event.startDate && event.endDate && event.startTime && event.endTime) {
                let startFull = this.setCombinedTimeString(event.startDate, event.startTime, 'start');
                let endFull = this.setCombinedTimeString(event.endDate, event.endTime, 'end');
                return await axios
                    .post('/events', {start: startFull, end: endFull}, {headers: {'X-Dry-Run': true}})
                    .catch(error => event.error = error.response.data.errors);
            }

        },
        addMinutes(date, minutes) {
            date.setMinutes(date.getMinutes() + minutes);
            return date;
        },
        setCombinedTimeString(date, time, target) {
            let combinedDateString = (date.toString() + ' ' + time);
            const offset = new Date(combinedDateString).getTimezoneOffset()

            if (target === 'start') {
                if (offset === -60) {
                    return new Date(
                        new Date(combinedDateString).setMinutes(new Date(combinedDateString).getMinutes() + 60)
                    ).toISOString().slice(0, 16);
                } else {
                    return new Date(
                        new Date(combinedDateString).setMinutes(new Date(combinedDateString).getMinutes() + 120)
                    ).toISOString().slice(0, 16);
                }
            } else if (target === 'end') {
                if (offset === -60) {
                    return new Date(
                        new Date(combinedDateString).setMinutes(new Date(combinedDateString).getMinutes() + 60)
                    ).toISOString().slice(0, 16);
                } else {
                    return new Date(
                        new Date(combinedDateString).setMinutes(new Date(combinedDateString).getMinutes() + 120)
                    ).toISOString().slice(0, 16);
                }
            }
        },
        getNextHourString(timeString) {
            let hours = timeString.slice(0, 2);
            let minutes = timeString.slice(3, 5);
            if ((Number(hours) + 1) < 10) {
                return '0' + (Number(hours) + 1) + ':' + minutes;
            } else {
                return (Number(hours) + 1) + ':' + minutes;
            }

        },
        deleteProject(event) {
            event.project = null;
            event.projectId = null;
            event.projectName = '';
        },
        /**
         * Creates an event and reloads all events
         *
         * @returns {Promise<*>}
         */
        async updateOrCreateEvent(event) {
            if (this.removeNotificationOnAction && (this.selectedRoom?.everyone_can_book || this.isAdmin)) {
                this.isOption = true;
            }
            return await axios
                .put('/events/' + event?.id, this.eventData(event))
                .then(() => this.closeModal(this.removeNotificationOnAction === true))
                .catch(error => event.error = error.response.data.errors);
        },
        openDeleteEventModal(event) {
            this.eventToDelete = event;
            this.deleteComponentVisible = true;
        },
        afterConfirm(bool) {
            if (!bool) return this.deleteComponentVisible = false;

            router.delete(`/events/${this.eventToDelete.id}`, {
                preserveScroll: true,
                preserveState: true,
                onSuccess: () => {
                    //this.closeModal();
                }
            })
        },
        eventData(event) {
            return {
                title: event.title,
                eventName: event.eventName,
                start: this.formatDate(event.startDate, event.startTime),
                end: this.formatDate(event.endDate, event.endTime),
                roomId: event.roomId,
                description: event.description,
                audience: event.audience,
                isLoud: event.isLoud,
                eventNameMandatory: this.eventTypes.find(eventType => eventType.id === event.eventType.id)?.individual_name,
                projectId: event.showProjectInfo ? event.projectId : null,
                projectName: event.creatingProject ? event.projectName : '',
                eventTypeId: event.eventType.id,
                projectIdMandatory: this.eventTypes.find(eventType => eventType.id === event.eventType.id)?.project_mandatory && !this.creatingProject,
                creatingProject: event.creatingProject,
                isOption: this.isOption,
                allDay: event.allDay,
                is_series: event.series ? event.series : false
            };
        },
    },
}
</script>
