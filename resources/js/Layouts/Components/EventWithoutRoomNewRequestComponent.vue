<template>
    <jet-dialog-modal :show="true" @close="closeModal(false)">
        <template #content>
            <img alt="Terminkonflikt" src="/Svgs/Overlays/illu_appointment_warning.svg" class="-ml-6 -mt-8 mb-4"/>
            <XIcon @click="closeModal()" class="h-5 w-5 right-0 top-0 mt-8 mr-5 absolute cursor-pointer"
                   aria-hidden="true"/>
            <div class="mx-4">
                <!--    Heading    -->
                <div>

                    <div class="flex w-full justify-between">
                        <h1 class="my-1 flex">
                            <div class="flex-grow headline1">
                                Termin ohne Raum
                            </div>
                        </h1>
                        <div class="flex justify-end">
                            <div class="flex justify-end">
                                <div class="flex mt-1 mr-2 cursor-pointer" @click="openDeleteEventModal(event)">
                                    <img class="bg-buttonBlue hover:bg-buttonHover h-8 w-8 p-1 rounded-full"
                                         src="/Svgs/IconSvgs/icon_trash_white.svg"/>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div v-if="event.declined_room_id" class="flex justify-start my-auto items-center mt-3.5 text-error line-through">
                        {{ this.rooms.find(room => room.id === event.declined_room_id)?.name }}
                    </div>
                    <h2 class="xsLight">
                        Diese Raumbelegungsanfrage wurde vom Raumadmin abgelehnt. Sage den Termin ab oder verschiebe
                        ihn in einen anderen Raum.
                    </h2>
                </div>
                <div class="flex items-center mb-1 my-4">
                    <div class="truncate flex xxsDark max-w-60 ">
                        Erstellt von
                        <div class="xxsDarkBold ml-1"> {{ event.creator.first_name }}
                            {{ event.creator.last_name }}
                        </div>
                    </div>
                    <img
                        :data-tooltip-target="event.creator.id" :src="event.creator.profile_photo_url"
                        :alt="event.creator.last_name"
                        class="ml-2 ring-white ring-2 rounded-full h-6 w-6 object-cover"/>
                </div>
                <!--    Form    -->
                <div>
                    <!--    Type and Title    -->
                    <div class="flex py-2">
                        <div class="w-1/2">
                            <Listbox as="div" class="flex h-12 mr-2" v-model="event.eventTypeId"
                                     :onchange="checkCollisions(event)" id="eventType">
                                <ListboxButton
                                    class="pl-3 border-2 border-gray-300 w-full bg-white relative font-semibold py-2 text-left cursor-pointer focus:outline-none sm:text-sm">
                                    <div class="flex items-center my-auto">
                                        <EventTypeIconCollection :height="20" :width="20"
                                                                 :iconName="this.eventTypes.find(type => type.id === event.eventTypeId)?.svg_name"/>
                                        <span class="block truncate items-center ml-3 flex">
                                            <span>{{
                                                    this.eventTypes.find(type => type.id === event.eventTypeId)?.name
                                                }}</span>
                                </span>
                                        <span
                                            class="ml-2 right-0 absolute inset-y-0 flex items-center pr-2 pointer-events-none">
                                     <ChevronDownIcon class="h-5 w-5 text-primary" aria-hidden="true"/>
                                </span>
                                    </div>
                                </ListboxButton>

                                <transition leave-active-class="transition ease-in duration-100"
                                            leave-from-class="opacity-100" leave-to-class="opacity-0">
                                    <ListboxOptions
                                        class="absolute w-64 z-10 mt-12 bg-primary shadow-lg max-h-32 pr-2 pt-2 pb-2 text-base ring-1 ring-black ring-opacity-5 overflow-y-auto focus:outline-none sm:text-sm">
                                        <ListboxOption as="template" class="max-h-8"
                                                       v-for="eventType in eventTypes"
                                                       :key="eventType.name"
                                                       :value="eventType.id"
                                                       v-slot="{ active, selected }">
                                            <li :class="[active ? ' text-white' : 'text-secondary', 'group hover:border-l-4 hover:border-l-success cursor-pointer flex justify-between items-center py-2 pl-3 pr-9 text-sm subpixel-antialiased']">
                                                <div class="flex">
                                                    <EventTypeIconCollection :height="12" :width="12"
                                                                             :iconName="eventType?.svg_name"/>
                                                    <span
                                                        :class="[selected ? 'xsWhiteBold' : 'font-normal', 'ml-4 block truncate']">
                                                        {{ eventType.name }}
                                                    </span>
                                                </div>
                                                <span
                                                    :class="[active ? ' text-white' : 'text-secondary', ' group flex justify-end items-center text-sm subpixel-antialiased']">
                                                      <CheckIcon v-if="selected" class="h-5 w-5 flex text-success"
                                                                 aria-hidden="true"/>
                                                </span>
                                            </li>
                                        </ListboxOption>
                                    </ListboxOptions>
                                </transition>
                            </Listbox>
                            <p class="text-xs text-red-800">{{ event.error?.eventType?.join('. ') }}</p>
                        </div>

                        <div class="w-1/2">
                            <input
                                v-if="this.eventTypes.find(type => type.id === event.eventTypeId)?.individual_name"
                                type="text"
                                v-model="event.eventName"
                                id="eventTitle"
                                placeholder="Terminname*"
                                class="h-12 sDark inputMain placeholder:xsLight placeholder:subpixel-antialiased focus:outline-none focus:ring-0 focus:border-secondary focus:border-1 w-full border-gray-300"/>
                            <input v-else type="text"
                                   v-model="event.eventName"
                                   id="eventTitle"
                                   placeholder="Terminname"
                                   class="h-12 sDark inputMain placeholder:xsLight placeholder:subpixel-antialiased focus:outline-none focus:ring-0 focus:border-secondary focus:border-1 w-full border-gray-300"/>

                            <p class="text-xs text-red-800">{{ event.error?.eventName?.join('. ') }}</p>
                        </div>

                    </div>
                    <!-- Attribute Menu -->
                    <Menu as="div" class="inline-block  text-left w-full">
                        <div>
                            <MenuButton
                                class="h-12 border-2 border-gray-300 w-full bg-white px-4 py-2 text-sm font-medium text-black focus:outline-none focus-visible:ring-2 focus-visible:ring-white "
                            >
                                        <span class="float-left flex xsLight subpixel-antialiased"><img
                                            src="/Svgs/IconSvgs/icon_adjustments.svg"
                                            class="mr-2"
                                            alt="attributeIcon"/>Termineigenschaften wählen</span>
                                <ChevronDownIcon
                                    class="ml-2 -mr-1 h-5 w-5 text-primary float-right"
                                    aria-hidden="true"
                                />
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
                                class="absolute overflow-y-auto h-24 mt-2 w-[80%] origin-top-left divide-y divide-gray-200 rounded-sm bg-primary ring-1 ring-black p-2 text-white opacity-100 z-50">
                                <div class="mx-auto w-full rounded-2xl bg-primary border-none mt-2">
                                    <div class="flex w-full mb-4">
                                        <input v-model="event.audience"
                                               type="checkbox"
                                               class="cursor-pointer h-6 w-6 text-success border-2 border-gray-300 focus:ring-0"/>
                                        <img src="/Svgs/IconSvgs/icon_public.svg" class="h-6 w-6 mx-2"
                                             alt="audienceIcon"/>

                                        <div
                                            :class="[event.audience ? 'text-white' : 'text-secondary', 'subpixel-antialiased']">
                                            Mit Publikum
                                        </div>
                                    </div>
                                    <div class="flex w-full mb-2">
                                        <input v-model="event.isLoud"
                                               type="checkbox"
                                               class="cursor-pointer h-6 w-6 text-success border-2 border-gray-300 focus:ring-0"/>
                                        <div
                                            :class="[event.isLoud ? 'text-white' : 'text-secondary', 'subpixel-antialiased mx-2']">
                                            Es
                                            wird laut
                                        </div>
                                    </div>
                                </div>
                            </MenuItems>
                        </transition>
                    </Menu>
                    <!--    Properties    -->
                    <div class="flex py-2">
                        <div v-if="event.audience">
                            <TagComponent icon="audience" displayed-text="Mit Publikum" hideX="true"></TagComponent>
                        </div>
                        <div v-if="event.isLoud">
                            <TagComponent displayed-text="es wird laut" hideX="true"></TagComponent>
                        </div>
                    </div>
                    <!--    Project    -->
                    <div>
                        <div class="xsLight flex" v-if="!event.creatingProject">
                            Aktuell zugeordnet zu:
                            <a v-if="event.project_id"
                               :href="route('projects.show', {project: event.project_id, openTab: 'calendar'})"
                               class="ml-3 flex xsDark">
                                {{ this.projects.find(project => project.id === event.project_id)?.name }}
                            </a>
                            <div v-else class="xsDark ml-2">
                                {{ this.projects.find(project => project.id === event.project_id)?.name ?? 'Keinem Projekt' }}
                            </div>
                            <div v-if="event.project_id && event.canEdit" class="flex items-center my-auto">
                                <button type="button"
                                        @click="this.deleteProject(event)">
                                    <XCircleIcon class="pl-2 h-6 w-6 hover:text-error text-primary"/>
                                </button>
                            </div>
                        </div>
                        <div class="xsLight" v-if="event.creatingProject">
                            Das Projekt wird beim Abspeichern erstellt.
                        </div>
                        <div class="my-2">
                            <div class="flex pb-2">
                            <span class="mr-4 text-sm"
                                  :class="[!event.creatingProject ? 'xsDark' : 'xsLight', '']">
                                Bestehendes Projekt
                            </span>
                                <label for="project-toggle"
                                       class="inline-flex relative items-center cursor-pointer">
                                    <input type="checkbox"
                                           v-model="event.creatingProject"
                                           id="project-toggle"
                                           class="sr-only peer">
                                    <div class="w-9 h-5 bg-gray-200 rounded-full
                            peer-checked:after:translate-x-full peer-checked:after:border-white
                            after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300
                            after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-blue-600">
                                    </div>
                                </label>
                                <span class="ml-4 text-sm"
                                      :class="[event.creatingProject ? 'xsDark' : 'xsLight', '']">
                                Neues Projekt
                            </span>
                                <div v-if="showHints" class="ml-3 flex">
                                    <SvgCollection svgName="arrowLeft" class="mt-1"/>
                                    <div class="font-nanum text-secondary ml-1 my-auto text-sm">
                                        Lege gleichzeitig ein neues Projekt an
                                    </div>
                                </div>
                            </div>
                            <input type="text"
                                   id="projectName"
                                   @focusin="event.showProjectSearchResults = true"
                                   @change="this.projectName = event.projectName"
                                   @focusout="this.projectName = '';"
                                   v-model="event.projectName"
                                   autocomplete="off"
                                   :placeholder="creatingProject ? 'Neuer Projektname' : 'Projekt suchen'"
                                   class="h-10 border-2 focus:outline-none focus:ring-0 focus:border-secondary focus:border-1 w-full border-gray-300"/>

                            <div
                                v-if="projectSearchResults.length > 0 && !event.creatingProject && event.showProjectSearchResults"
                                class="absolute bg-primary truncate sm:text-sm w-10/12">
                                <div v-for="(project, index) in projectSearchResults"
                                     :key="index"
                                     @click="event.projectId = project.id; event.project = project; event.projectName = ''; this.projectName = ''; event.showProjectSearchResults = false; this.projectSearchResults = [];"
                                     class="p-4 text-white border-l-4 hover:border-l-success border-l-primary cursor-pointer">
                                    {{ project.name }}
                                </div>
                            </div>
                            <p class="text-xs text-red-800">{{ event.error?.projectId?.join('. ') }}</p>
                            <p class="text-xs text-red-800">{{ event.error?.projectName?.join('. ') }}</p>
                        </div>
                    </div>

                    <!--    Time    -->
                    <div class="flex py-1 flex-col sm:flex-row align-baseline">
                        <div class="sm:w-1/2">
                            <label for="startDate" class="xsLight">Start</label>
                            <div class="w-full flex">
                                <input v-model="event.startDate"
                                       id="startDate"
                                       @change="checkChanges()"
                                       type="date"
                                       required
                                       class="border-gray-300 border-2  disabled:border-none flex-grow"/>
                                <input v-model="event.startTime"
                                       id="changeStartTime"
                                       @change="checkChanges()"
                                       type="time"
                                       required
                                       class="border-gray-300 border-2  disabled:border-none"/>
                            </div>
                            <p class="text-xs text-red-800">{{ event.error?.start?.join('. ') }}</p>
                        </div>
                        <div class="sm:w-1/2">
                            <label for="endDate" class="xsLight">Ende</label>
                            <div class="w-full flex">
                                <input v-model="event.endDate"
                                       id="endDate"
                                       @change="checkChanges()"
                                       type="date"
                                       required
                                       class="border-gray-300 border-2 disabled:border-none flex-grow"/>
                                <input v-model="event.endTime"
                                       id="changeEndTime"
                                       @change="checkChanges()"
                                       type="time"
                                       required
                                       class="border-gray-300 border-2 disabled:border-none"/>
                            </div>
                            <p class="text-xs text-red-800">{{ event.error?.end?.join('. ') }}</p>
                        </div>

                    </div>

                    <!--    Room    -->
                    <div class="py-1 my-1">
                        <Listbox as="div" v-model="event.room_id" id="room">
                            <ListboxButton
                                class="border-2 border-gray-300 w-full h-10 cursor-pointer truncate flex p-2">
                                <div v-if="event.room_id" class="flex-grow text-left">
                                    {{ this.rooms.find(room => room.id === event.room_id)?.name }}
                                </div>
                                <div v-else class="flex-grow xsLight text-left subpixel-antialiased">
                                    Raum wählen*
                                </div>
                                <ChevronDownIcon class="h-5 w-5 text-primary" aria-hidden="true"/>
                            </ListboxButton>
                            <ListboxOptions class="w-[80%] bg-primary max-h-32 overflow-y-auto text-sm absolute">
                                <ListboxOption v-for="room in rooms"
                                               class="hover:bg-indigo-800 text-secondary cursor-pointer p-2 flex justify-between "
                                               :key="room.name"
                                               :value="room.id"
                                               v-slot="{ active, selected }">
                                    <div :class="[selected ? 'text-white' : '']">
                                        {{ room.name }}
                                    </div>
                                    <CheckIcon v-if="selected" class="h-5 w-5 text-success" aria-hidden="true"/>
                                </ListboxOption>
                            </ListboxOptions>
                        </Listbox>
                        <p class="text-xs text-red-800">{{ event.error?.room_id?.join('. ') }}</p>
                    </div>
                    <!--    Description    -->
                    <div class="py-1">
                            <textarea placeholder="Was gibt es bei dem Termin zu beachten?"
                                      id="description"
                                      v-model="event.description"
                                      rows="4"
                                      class="border-gray-300 border-2 resize-none w-full text-sm focus:outline-none focus:ring-0 focus:border-secondary focus:border-1 w-full border-gray-300"/>
                    </div>
                    <div class="flex justify-center w-full py-4">
                        <button class="bg-buttonBlue hover:bg-indigo-600 py-2 px-8 rounded-full text-white"
                                @click="updateOrCreateEvent(event)">
                            {{
                                (selectedRoom?.everyone_can_book || this.isAdmin) ? 'Speichern' : 'Belegung anfragen'
                            }}
                        </button>
                    </div>
                </div>
            </div>
        </template>
    </jet-dialog-modal>

    <!-- Event löschen Modal -->
    <confirmation-component
        v-if="deleteComponentVisible"
        confirm="Löschen"
        titel="Termin löschen"
        :description="'Bist du sicher, dass du den Termin ' + this.eventToDelete.title + ' in den Papierkorb legen möchtest? Du kannst ihn innerhalb von 30 Tagen wiederherstellen.'"
        @closed="afterConfirm"/>

</template>

<script>

import JetDialogModal from "@/Jetstream/DialogModal";
import {ChevronDownIcon, DotsVerticalIcon, PencilAltIcon, XCircleIcon, XIcon} from '@heroicons/vue/outline';
import EventTypeIconCollection from "@/Layouts/Components/EventTypeIconCollection";
import {
    Listbox,
    ListboxButton,
    ListboxOption,
    ListboxOptions,
    Menu,
    MenuButton,
    MenuItem,
    MenuItems
} from "@headlessui/vue";
import {CheckIcon, ChevronUpIcon, TrashIcon} from "@heroicons/vue/solid";
import SvgCollection from "@/Layouts/Components/SvgCollection";
import Input from "@/Jetstream/Input";
import ConfirmationComponent from "@/Layouts/Components/ConfirmationComponent";
import TagComponent from "@/Layouts/Components/TagComponent";

export default {
    name: 'EventWithoutRoomComponent',

    components: {
        Input,
        JetDialogModal,
        XIcon,
        XCircleIcon,
        EventTypeIconCollection,
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
            collisionCount: 0,
            description: null,
            canEdit: false,
            deleteComponentVisible: false,
            eventToDelete: null,
        }
    },

    props: ['showHints', 'eventTypes', 'rooms', 'event', 'projects', 'isAdmin'],

    emits: ['closed'],

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
        },
        event: {
            immediate: true,
            deep: true,
            handler: function () {
                this.openModal()
            },
        },
    },
    computed: {
        eventToShow: function () {
            let eventToShow = this.event;

            eventToShow.startDate = new Date(this.event.start).format('YYYY-MM-DD');
            eventToShow.startTime = new Date(this.event.start).format('HH:mm');
            eventToShow.endDate = new Date(this.event.end).format('YYYY-MM-DD');
            eventToShow.endTime = new Date(this.event.end).format('HH:mm');
            eventToShow.creatingProject = false;
            return eventToShow;
        },

    },

    methods: {
        openModal() {

        },

        closeModal(bool) {
            this.$emit('closed', bool);
        },
        formatFullDate(isoDate) {
            return isoDate.split('T')[0].substring(8, 10) + '.' + isoDate.split('T')[0].substring(5, 7) + '.' + isoDate.split('T')[0].substring(0, 4) + ', ' + isoDate.split('T')[1].substring(0, 5)
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
            this.checkCollisions(event)
        },
        checkTypeChange(event) {

            this.checkCollisions(event);
        },

        /**
         * If the user selects a start, end, and room
         * call the server to get information if there are any collision
         *
         * @returns {Promise<void>}
         */
        async checkCollisions(event) {
            if (!(event.startTime && event.startDate && event.endTime && event.endDate && event.room_id)) {
                event.collisionCount = 0
                return;
            }

            await axios
                .get('/events/collision', {
                    params: {
                        start: this.formatDate(event.startDate, event.startTime),
                        end: this.formatDate(event.endDate, event.endTime),
                        roomId: event.room_id,
                    }
                })
                .then(response => event.collisionCount = response.data);
        },
        updateTimes(event) {
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
        setCombinedTimeString(date, time, target) {
            let combinedDateString = (date.toString() + ' ' + time);
            const offset = new Date(combinedDateString).getTimezoneOffset()

            if (target === 'start') {
                if (offset === -60) {
                    return new Date(new Date(combinedDateString).setMinutes(new Date(combinedDateString).getMinutes() + 60)).toISOString().slice(0, 16);
                } else {
                    return new Date(new Date(combinedDateString).setMinutes(new Date(combinedDateString).getMinutes() + 120)).toISOString().slice(0, 16);
                }
            } else if (target === 'end') {
                if (offset === -60) {
                    return new Date(new Date(combinedDateString).setMinutes(new Date(combinedDateString).getMinutes() + 60)).toISOString().slice(0, 16);
                } else {
                    return new Date(new Date(combinedDateString).setMinutes(new Date(combinedDateString).getMinutes() + 120)).toISOString().slice(0, 16);
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
        },

        /**
         * Creates an event and reloads all events
         *
         * @returns {Promise<*>}
         */
        async updateOrCreateEvent(event) {

            return await axios
                .put('/events/' + event?.id, this.eventData(event))
                .then(() => this.closeModal())
                .catch(error => event.error = error.response.data.errors);
        },
        openDeleteEventModal(event) {
            this.eventToDelete = event;
            this.deleteComponentVisible = true;
        },
        async afterConfirm(bool) {
            if (!bool) return this.deleteComponentVisible = false;

            return await axios
                .delete(`/events/${this.eventToDelete.id}`)
                .then(() => this.closeModal());
        },

        eventData(event) {
            return {
                title: event.title,
                eventName: event.eventName,
                start: this.formatDate(event.startDate, event.startTime),
                end: this.formatDate(event.endDate, event.endTime),
                roomId: event.room_id,
                description: event.description,
                audience: event.audience,
                isLoud: event.isLoud,
                eventNameMandatory: this.eventTypes.find(eventType => eventType.id === event.eventTypeId)?.individual_name,
                projectId: event.projectId,
                projectName: event.creatingProject ? event.projectName : '',
                eventTypeId: event.eventTypeId,
                projectIdMandatory: this.eventTypes.find(eventType => eventType.id === event.eventTypeId)?.project_mandatory && !this.creatingProject,
                creatingProject: event.creatingProject,
            };
        },
    },
}
</script>

<style scoped></style>
