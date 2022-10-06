<template>
    <jet-dialog-modal :show="true" @close="closeModal()">
        <template #content>
            <img alt="Neuer Termin" src="/Svgs/Overlays/illu_appointment_new.svg" class="-ml-6 -mt-8 mb-4"/>
            <XIcon @click="closeModal()" class="h-5 w-5 right-0 top-0 mt-8 mr-5 absolute cursor-pointer" aria-hidden="true"/>
            <div class="mx-4">
                <!--    Heading    -->
                <div>
                    <h1 class="my-2 flex">
                        <div class="flex-grow text-3xl">
                            {{ this.event?.id ? 'Event bearbeiten' : 'Neue Raumbelegung' }}
                        </div>
                        <Menu as="div" v-if=" this.event?.id">
                            <MenuButton class="m-4">
                                <DotsVerticalIcon class="h-6 w-6 text-gray-600" aria-hidden="true"/>
                            </MenuButton>

                            <MenuItems class="absolute z-40 right-0 w-72 shadow-lg rouned rounded-md bg-gray-100">
                                <MenuItem v-if="event?.canAccept && event?.occupancy_option"
                                    @click="approveRequest(selectedEvent, true)"
                                    class="hover:bg-indigo-900 hover:text-white p-2 rounded-md">
                                    <div class="flex">
                                        <CheckIcon class="mr-3 h-5 w-5" aria-hidden="true"/>
                                        <div> Raumbelegung zusagen</div>
                                    </div>
                                </MenuItem>
                                <MenuItem v-if="event?.canAccept && event?.occupancy_option"
                                    @click="approveRequest(selectedEvent, false)"
                                    class="hover:bg-indigo-900 hover:text-white p-2 rounded-md">
                                    <div class="flex">
                                        <XIcon class="mr-3 h-5 w-5" aria-hidden="true"/>
                                        <div> Raumbelegung absagen</div>
                                    </div>
                                </MenuItem>
                                <MenuItem v-if="event?.canDelete"
                                    @click="deleteComponentVisible = true"
                                    class="hover:bg-indigo-900 hover:text-white p-2 rounded-md">
                                    <div class="flex">
                                        <TrashIcon class="mr-3 h-5 w-5" aria-hidden="true"/>
                                        Termin löschen
                                    </div>
                                </MenuItem>
                            </MenuItems>
                        </Menu>
                    </h1>
                    <h2 class="text-secondary">
                        Bitte beachte, dass du Vor- und Nachbereitungszeit einplanst.
                    </h2>
                    <div v-if="showHints" class="mt-6 font-nanum text-secondary">
                        Hier kannst du die Art des Termins definieren. ihn einem Projekt zuordnen
                        und weitere Infos mit deinem Team teilen.
                        Anschließend kannst du dafür die Raumbelegung anfragen.
                    </div>

                </div>

                <!--    Form    -->
                <!--    Type and Title    -->
                <div class="flex py-4">
                    <div class="w-1/2">
                        <label for="eventType" class="text-xs text-secondary">Typ</label>
                        <div class=" w-full h-10 cursor-pointer truncate p-2" v-if="!canEdit">
                            {{ selectedEventType?.name }}
                        </div>
                        <Listbox as="div" v-model="selectedEventType" v-if="canEdit" :onchange="checkCollisions()" id="eventType">
                            <ListboxButton class="border border-gray-300 w-full h-10 cursor-pointer truncate flex p-2">
                                <div class="flex-grow text-left">
                                    <EventTypeIconCollection :height="20" :width="20" :iconName="selectedEventType?.svg_name"/>
                                    {{ selectedEventType?.name }}
                                </div>
                                <ChevronDownIcon class="h-5 w-5 text-primary" aria-hidden="true"/>
                            </ListboxButton>

                            <ListboxOptions class="w-72 bg-primary max-h-32 overflow-y-auto text-sm absolute">
                                <ListboxOption v-for="eventType in eventTypes"
                                    class="p-2 hover:bg-indigo-800 text-secondary cursor-pointer flex items-center justify-between"
                                    :key="eventType.name"
                                    :value="eventType"
                                    v-slot="{ active, selected }">
                                    <div :class="[selected ? 'font-bold text-white' : '']">
                                        <EventTypeIconCollection :height="12" :width="12" :iconName="eventType?.svg_name"/>
                                        {{ eventType.name }}
                                    </div>
                                    <CheckIcon v-if="selected" class="h-5 w-5 text-success" aria-hidden="true"/>
                                </ListboxOption>
                            </ListboxOptions>
                        </Listbox>
                        <p class="text-xs text-red-800">{{ error?.eventType?.join('. ') }}</p>
                    </div>

                    <div class="w-1/2 pl-4">
                        <label for="eventTitle" class="text-xs text-secondary">Titel</label>
                        <input type="text"
                            v-model="title"
                            id="eventTitle"
                            :disabled="!canEdit || selectedEventType?.project_mandatory"
                            class="h-10 focus:outline-none focus:border-secondary focus:border-1 border-gray-300 w-full disabled:border-none"/>

                        <p class="text-xs text-red-800">{{ error?.title?.join('. ') }}</p>
                    </div>
                </div>

                <!--    Properties    -->
                <div class="flex py-4">
                    <div class="flex align-middle w-1/2">
                        <input v-model="audience"
                            :disabled="!canEdit"
                            type="checkbox"
                            class="cursor-pointer h-6 w-6 text-buttonBlue border-2 border-gray-300 focus:ring-0"/>
                        <img src="/Svgs/IconSvgs/icon_public.svg" class="h-6 w-6 mx-2" alt="audienceIcon"/>
                        <div :class="[audience ? 'text-buttonBlue' : 'text-secondary']">Publikum</div>
                    </div>
                    <div class="flex align-middle w-1/2 pl-4">
                        <input v-model="isLoud"
                            :disabled="!canEdit"
                            type="checkbox"
                            class="cursor-pointer h-6 w-6 text-buttonBlue border-2 border-gray-300 focus:ring-0"/>
                        <img src="/Svgs/IconSvgs/icon_loud.svg" class="h-6 w-6 mx-2" alt="isLoudIcon"/>
                        <div :class="[isLoud ? 'text-buttonBlue' : 'text-secondary']">Es wird laut</div>
                    </div>
                </div>

                <!--    Project    -->
                <div>
                    <div class="text-secondary my-4 flex" v-if="!this.creatingProject">
                        Aktuell zugeordnet zu:
                        <div class="text-primary">
                            {{ this.selectedProject?.name ?? 'Keinem Projekt' }}
                        </div>
                        <button type="button"
                            @click="selectedProject = null"
                            v-if="this.selectedProject && this.canEdit">
                            <XCircleIcon class="pl-2 pt-2 h-6 w-6 hover:text-error "/>
                        </button>
                    </div>
                    <div class="text-secondary my-4" v-if="this.creatingProject">
                        Das Projekt wird beim Abspeichern erstellt.
                    </div>

                    <div class="my-4" v-if="this.canEdit">
                        <div class="flex pb-2">
                            <label for="projectName" class="text-xs text-secondary flex-grow">
                                {{ creatingProject ? 'Neues Projekt' : 'Bestehendes Projekt' }}
                            </label>

                            <label for="project-toggle" class="inline-flex relative items-center cursor-pointer">
                                <input type="checkbox"
                                    v-model="creatingProject"
                                    :disabled="!canEdit"
                                    id="project-toggle"
                                    class="sr-only peer">
                                <div class="w-9 h-5 bg-gray-200 rounded-full
                            peer-checked:after:translate-x-full peer-checked:after:border-white
                            after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300
                            after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-blue-600">
                                </div>
                            </label>

                            <div v-if="showHints" class="ml-3 flex">
                                <SvgCollection svgName="arrowLeft" class="mt-1"/>
                                <div class="font-nanum text-secondary ml-1 my-auto text-sm">
                                    Lege gleichzeitig ein neues Projekt an
                                </div>
                            </div>
                        </div>

                        <input type="text"
                            id="projectName"
                            v-model="projectName"
                            :placeholder="creatingProject ? 'Neuer Projektname' : 'Projekt suchen'"
                            class="h-10 focus:outline-none border-gray-300 w-full disabled:border-none"/>

                        <div v-if="projectSearchResults.length > 0 && !creatingProject"
                            class="absolute bg-primary truncate sm:text-sm w-10/12">
                            <div v-for="(project, index) in projectSearchResults"
                                :key="index"
                                @click="selectedProject = project; projectName = ''"
                                class="p-4 text-white border-l-4 hover:border-l-success border-l-primary cursor-pointer">
                                {{ project.name }}
                            </div>
                        </div>

                        <p class="text-xs text-red-800">{{ error?.projectId?.join('. ') }}</p>
                        <p class="text-xs text-red-800">{{ error?.projectName?.join('. ') }}</p>
                    </div>
                </div>

                <!--    Time    -->
                <div class="flex py-4 flex-col sm:flex-row align-baseline">
                    <div class="sm:w-1/2">
                        <label for="startDate" class="text-secondary text-xs">Start</label>
                        <div class="w-full flex">
                            <input v-model="startDate"
                                id="startDate"
                                @change="checkCollisions()"
                                type="date"
                                :disabled="!canEdit"
                                required
                                class="border-gray-300  disabled:border-none flex-grow"/>
                            <input v-model="startTime"
                                id="changeStartTime"
                                @change="checkCollisions()"
                                type="time"
                                :disabled="!canEdit"
                                required
                                class="border-gray-300  disabled:border-none"/>
                        </div>
                        <p class="text-xs text-red-800">{{ error?.start?.join('. ') }}</p>
                    </div>
                    <div class="px-2 pt-8">-</div>
                    <div class="sm:w-1/2">
                        <label for="endDate" class="text-secondary text-xs">Ende</label>
                        <div class="w-full flex">
                            <input v-model="endDate"
                                id="endDate"
                                @change="checkCollisions()"
                                type="date"
                                required
                                :disabled="!canEdit"
                                class="border-gray-300  disabled:border-none flex-grow"/>
                            <input v-model="endTime"
                                id="changeEndTime"
                                @change="checkCollisions()"
                                type="time"
                                required
                                :disabled="!canEdit"
                                class="border-gray-300  disabled:border-none"/>
                        </div>
                        <p class="text-xs text-red-800">{{ error?.end?.join('. ') }}</p>
                    </div>

                </div>

                <!--    Room    -->
                <div class="py-4">
                    <label for="room" class="text-xs text-secondary">Raum</label>
                    <div class=" w-full h-10 cursor-pointer truncate p-2" v-if="!canEdit">
                        {{ selectedEventType?.name }}
                    </div>
                    <Listbox as="div" v-model="selectedRoom" id="room" v-if="canEdit">
                        <ListboxButton class="border border-gray-300 w-full h-10 cursor-pointer truncate flex p-2">
                            <div class="flex-grow text-left">
                                {{ selectedRoom?.name }}
                            </div>
                            <ChevronDownIcon class="h-5 w-5 text-primary" aria-hidden="true"/>
                        </ListboxButton>
                        <ListboxOptions class="w-5/6 bg-primary max-h-32 overflow-y-auto text-sm absolute">
                            <ListboxOption v-for="room in rooms"
                                class="hover:bg-indigo-800 text-secondary cursor-pointer p-2 flex justify-between "
                                :key="room.name"
                                :value="room"
                                v-slot="{ active, selected }">
                                <div :class="[selected ? 'text-white' : '']">
                                    {{ room.name }}
                                </div>
                                <CheckIcon v-if="selected" class="h-5 w-5 text-success" aria-hidden="true"/>
                            </ListboxOption>
                        </ListboxOptions>
                    </Listbox>
                    <p class="text-xs text-red-800">{{ error?.roomId?.join('. ') }}</p>
                </div>

                <div v-if="collisionCount > 0" class="bg-error text-sm text-white rounded-md p-2 flex">
                    <img src="/Svgs/IconSvgs/icon_warning_white.svg" class="h-8 w-8 p-2" aria-hidden="true" alt="warnIcon"/>
                    <div>
                        Dieser Termin überschneidet sich mit {{ collisionCount }} Terminen im selben Raum.
                        Diese könnten anderen Projekten zugeordnet sein.
                    </div>
                </div>

                <!--    Description    -->
                <div class="py-4">
                    <label for="description" class="text-xs text-secondary">Notizen</label>
                    <textarea placeholder="Was gibt es bei dem Termin zu beachten?"
                        id="description"
                        :disabled="!canEdit"
                        v-model="description"
                        rows="4"
                        class="border-gray-300 w-full text-sm disabled:border-none"/>
                </div>

                <div class="flex justify-center w-full py-4" v-if="canEdit">
                    <button class="bg-buttonBlue hover:bg-indigo-600 py-2 px-4 uppercase rounded-full text-white"
                        @click="updateOrCreateEvent()">
                        {{ (isAdmin || selectedRoom.everyone_can_book) ? 'Speichern' : 'Belegung anfragen' }}
                    </button>
                </div>
            </div>
        </template>
    </jet-dialog-modal>

    <!-- Event löschen Modal -->
    <confirmation-component
        v-if="deleteComponentVisible"
        confirm="Löschen"
        titel="Event löschen"
        :description="'Bist du sicher, dass du ' + event.title + ' aus dem System löschen möchtest?'"
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

export default {
    name: 'EventComponent',

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
            eventTypeName: null,
            selectedEventType: null,
            selectedProject: null,
            selectedRoom: null,
            error: null,
            creatingProject: false,
            projectSearchResults: [],
            collisionCount: 0,
            description: null,
            canEdit: false,
            deleteComponentVisible: false,
        }
    },

    props: ['showHints', 'eventTypes', 'rooms', 'isAdmin', 'event'],

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

    methods: {
        openModal() {
            this.canEdit = (!this.event?.id) || this.event?.canEdit;
            if (!this.event) return;

            this.startDate = this.event.start.format('YYYY-MM-DD');
            this.startTime = this.event.start.format('HH:mm');
            this.endDate = this.event.end.format('YYYY-MM-DD');
            this.endTime = this.event.end.format('HH:mm');
            this.isLoud = this.event.isLoud
            this.audience = this.event.audience
            this.title = this.event.title
            this.selectedEventType = this.eventTypes.find(type => type.id === this.event.eventTypeId)
            this.selectedProject = {id: this.event.projectId, name: this.event.projectName}
            this.selectedRoom = this.rooms.find(type => type.id === this.event.roomId)
            this.description = this.event.description

            this.checkCollisions();
        },

        closeModal(bool) {
            this.startDate = null;
            this.startTime = null;
            this.endDate = null;
            this.endTime = null;
            this.selectedRoom = null;
            this.selectedProject = null;
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

        /**
         * If the user selects a start, end, and room
         * call the server to get information if there are any collision
         *
         * @returns {Promise<void>}
         */
        async checkCollisions() {
            if (!(this.startTime && this.startDate && this.endTime && this.endDate && this.selectedRoom)) {
                this.collisionCount = 0
                return;
            }

            await axios
                .get('/events/collision', {
                    params: {
                        start: this.formatDate(this.startDate, this.startTime),
                        end: this.formatDate(this.endDate, this.endTime),
                        roomId: this.selectedRoom?.id,
                    }
                })
                .then(response => this.collisionCount = response.data);
        },

        /**
         * Creates an event and reloads all events
         *
         * @returns {Promise<*>}
         */
        async updateOrCreateEvent() {
            if (!this.event?.id) {
                return await axios
                    .post('/events', this.eventData())
                    .then(() => this.closeModal())
                    .catch(error => this.error = error.response.data.errors);
            }

            return await axios
                .put('/events/' + this.event?.id, this.eventData())
                .then(() => this.closeModal())
                .catch(error => this.error = error.response.data.errors);
        },

        async afterConfirm(bool) {
            console.log(bool)
            if (!bool) return this.deleteComponentVisible = false;

            return await axios
                .delete(`/events/${this.event.id}`)
                .then(() => this.closeModal());
        },

        async approveRequest(bool) {
            return await axios
                .patch('/events/' + this.event?.id + '/accept', {accepted: bool})
                .then(() => this.closeModal())
                .catch(error => this.error = error.response.data.errors);
        },

        eventData() {
            return {
                title: this.title,
                start: this.formatDate(this.startDate, this.startTime),
                end: this.formatDate(this.endDate, this.endTime),
                roomId: this.selectedRoom?.id,
                description: this.description,
                audience: this.audience,
                isLoud: this.isLoud,
                projectId: this.selectedProject?.id,
                projectName: this.projectName,
                eventTypeId: this.selectedEventType?.id,
            };
        },
    },
}
</script>

<style scoped></style>
