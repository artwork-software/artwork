<template>
    <!--  Hide Rooms  -->
    <div class="flex items-baseline flex-wrap p-3">
        <button v-for="(room, index) in displayedRooms"
            class="[room.hidden ? 'opacity-50' : '', 'button m-1 rounded-full bg-primary hover:bg-secondary text-white text-xs px-2 py-1']"
            :key="room.id"
            @click="displayedRooms[index].hide = !displayedRooms[index].hide">
            {{ room.label }}
        </button>
    </div>
    <button class="button m-1 rounded-full bg-primary hover:bg-secondary text-white px-2 py-1"
        @click="selectEvent(null)">
        Neuer Termin
    </button>

    <!--  Calendar  -->
    <div>
        <vue-cal
            style="height: 500px"
            today-button
            events-on-month-view="short"
            locale="de"

            :stickySplitLabels="true"
            :disable-views="['years']"
            :events="displayedEvents"
            :split-days="displayedRooms"
            :editable-events="{ title: false, drag: true, resize: false, delete: false, create: true }"
            :snap-to-time="15"
            :drag-to-create-threshold="15"
            :active-view="initialView ?? 'week'"

            @event-drag-create="selectEvent($event)"
            @event-focus="selectEvent($event)"

            @ready="fetchEvents"
            @view-change="fetchEvents"
        />
    </div>

    <!-- Event Detail Modal -->
    <jet-dialog-modal :show="selectedEvent" @close="selectedEvent = null">
        <template #content>
            <img src="/Svgs/Overlays/illu_appointment_new.svg" class="-ml-6 -mt-8 mb-4" alt="calendar-icon"/>
            <div class="mx-4">
                <div class="flex justify-between">
                    <div class="font-black font-lexend text-primary tracking-wide text-3xl my-2">
                        Event Details
                    </div>
                    <button @click="deleteEvent"
                        v-if="selectedEvent.id"
                        class="text-white bg-red-800 hover:bg-red-600 rounded-lg py-2 px-4 m-3">
                        Löschen
                    </button>
                </div>
                <XIcon @click="selectedEvent = null"
                    class="h-5 w-5 right-0 top-0 mt-8 mr-5 absolute cursor-pointer"
                    aria-hidden="true"/>
            </div>

            <form @submit.prevent="updateOrCreateEvent(selectedEvent)">

                <div class="my-5 w-full border-gray-200 border-b-2"></div>

                <div class="form-group flex flex-col md:flex-row justify-between my-2">
                    <label for="title" class="form-label w-full md:w-1/2 text-gray-700 mt-3">
                        Termin Title
                    </label>
                    <input id="title"
                        type="text"
                        class="rounded focus:border-indigo-300 w-full md:w-1/2"
                        v-model="selectedEvent.title"/>
                </div>
                <p class="text-xs text-red-800">{{ error?.title?.join('. ') }}</p>

                <div class="text-secondary text-xs">
                    Bitte beachte, dass du Vor- und Nachbereitungszeit einplanst.
                </div>

                <div class="form-group flex justify-between my-2">
                    <label for="start" class="form-label w-full md:w-1/2 text-gray-700 mt-3">Start</label>
                    <input id="start"
                        @change="checkCollisions"
                        type="datetime-local"
                        v-model="selectedEvent.start"
                        class="rounded focus:border-indigo-300 w-full md:w-1/2">
                </div>
                <p class="text-xs text-red-800">{{ error?.start?.join('. ') }}</p>

                <div class="form-group flex justify-between my-2">
                    <label for="end" class="form-label w-full md:w-1/2 text-gray-700 mt-3">Ende</label>
                    <input id="end"
                        @change="checkCollisions"
                        type="datetime-local"
                        v-model="selectedEvent.end"
                        class="rounded focus:border-indigo-300 w-full md:w-1/2">
                </div>
                <p class="text-xs text-red-800">{{ error?.end?.join('. ') }}</p>

                <div class="form-group flex flex-col md:flex-row justify-between my-2">
                    <label for="roomId" class="form-label w-full md:w-1/2 text-gray-700 mt-3">
                        Raum
                    </label>
                    <select id="roomId"
                        @change="checkCollisions"
                        class="rounded focus:border-indigo-300 w-full md:w-1/2"
                        v-model="selectedEvent.roomId">
                        <option v-for="room in rooms" :value="room.id">
                            {{ room.label }}
                        </option>
                    </select>
                </div>
                <p class="text-xs text-red-800">{{ error?.roomId?.join('. ') }}</p>

                <p v-if="collision" class="text-xs text-red-800">
                    Dieser Termin überschneidet sich mit {{ collision }} Terminen im selben Raum.
                    Diese könnten anderen Projekten zugeordnet sein.
                </p>

                <div class="my-5 w-full border-gray-200 border-b-2"></div>

                <div class="form-group flex justify-between my-2">
                    <label for="audience" class="form-label w-full md:w-1/2 text-gray-700 mt-3">
                        Wird Publikum anwesend sein?
                    </label>
                    <input type="checkbox"
                        id="audience"
                        value="audience"
                        class="w-10 h-10 rounded-full focus:border-indigo-300"
                        v-model="selectedEvent.audience">
                </div>
                <p class="text-xs text-red-800">{{ error?.audience?.join('. ') }}</p>

                <div class="form-group flex justify-between my-2">
                    <label for="isLoud" class="form-label w-full md:w-1/2 text-gray-700 mt-3">
                        Könnte es laut werden?
                    </label>
                    <input type="checkbox"
                        id="isLoud"
                        value="isLoud"
                        class="w-10 h-10 rounded-full focus:border-indigo-300"
                        v-model="selectedEvent.isLoud">
                </div>
                <p class="text-xs text-red-800">{{ error?.isLoud?.join('. ') }}</p>

                <div class="my-5 w-full border-gray-200 border-b-2"></div>

                <div class="form-group flex flex-col md:flex-row justify-between my-2">
                    <label for="projectId" class="form-label w-full md:w-1/2 text-gray-700 mt-3">
                        Bestehendem Projekt zuordnen
                    </label>
                    <select id="projectId"
                        class="rounded focus:border-indigo-300 w-full md:w-1/2"
                        v-model="selectedEvent.projectId">
                        <option :value="null">
                            Neues Projekt erstellen
                        </option>
                        <option v-for="project in projects" :value="project.id">
                            {{ project.label }}
                        </option>
                    </select>
                </div>
                <p class="text-xs text-red-800">{{ error?.projectId?.join('. ') }}</p>

                <div class="form-group flex flex-col md:flex-row justify-between my-2">
                    <label for="projectName" class="form-label w-full md:w-1/2 text-gray-700 mt-3">
                        Name für ein neues Projekt
                    </label>
                    <input id="projectName"
                        type="text"
                        class="rounded focus:border-indigo-300 w-full md:w-1/2 disabled:opacity-25"
                        :disabled="selectedEvent.projectId"
                        v-model="selectedEvent.projectName"/>
                </div>
                <p class="text-xs text-red-800">{{ error?.projectName?.join('. ') }}</p>

                <div class="text-secondary text-xs">
                    Der Name wird nur gespeichert, wenn du ein neues Projekt auswählst
                </div>

                <div class="form-group flex flex-col md:flex-row justify-between my-2">
                    <label for="type" class="form-label w-full md:w-1/2 text-gray-700 mt-3">Termin Typ</label>
                    <select id="type"
                        class="rounded focus:border-indigo-300 w-full md:w-1/2"
                        v-model="selectedEvent.eventTypeId">
                        <option v-for="type in types" :value="type.id">
                            <EventTypeIconCollection :height="12" :width="12" :iconName="type?.img"/>
                            {{ type.label }}
                        </option>
                    </select>
                </div>
                <p class="text-xs text-red-800">{{ error?.eventType?.join('. ') }}</p>

                <div class="my-5 w-full border-gray-200 border-b-2"></div>

                <div class="form-group flex flex-col md:flex-row justify-between my-2">
                    <label for="description" class="form-label w-full md:w-1/2 text-gray-700 mt-3">
                        Weitere Termin informationen:
                    </label>
                    <textarea id="description"
                        type="text"
                        class="rounded focus:border-indigo-300 w-full md:w-1/2"
                        v-model="selectedEvent.description"></textarea>
                </div>
                <p class="text-xs text-red-800">{{ error?.description?.join('. ') }}</p>

                <div class="my-5 w-full border-gray-200 border-b-2"></div>

                <div class="form-group flex flex-col md:flex-row justify-between my-2">
                    <button type="submit" class="text-white bg-primary hover:bg-blue-800 rounded-lg py-2 px-4 m-3 w-full">
                        Speichern
                    </button>
                </div>
            </form>

        </template>
    </jet-dialog-modal>
</template>

<script>

import VueCal from 'vue-cal'
import 'vue-cal/dist/vuecal.css'
import JetDialogModal from "@/Jetstream/DialogModal";
import {XCircleIcon, XIcon} from '@heroicons/vue/outline';
import EventTypeIconCollection from "@/Layouts/Components/EventTypeIconCollection";

export default {
    name: 'CalendarComponent',
    components: {VueCal, JetDialogModal, XIcon, XCircleIcon, EventTypeIconCollection},
    props: ['projectId', 'roomId', 'initialView'],
    data() {
        return {
            events: [],
            displayedEvents: [],
            areaFilter: [],
            roomFilter: [],
            typeFilter: [],
            rooms: [],
            displayedRooms: [],
            projects: [],
            selectedEvent: null,
            error: null,
            collision: 0,
            eventsSince: null,
            eventsUntil: null,
        }
    },
    methods: {
        /**
         * Fetch the events from server
         * initialise possible rooms, types and projects
         *
         * @param view
         * @param {Date} startDate
         * @param {Date} endDate
         * @returns {Promise<void>}
         */
        async fetchEvents({view, startDate, endDate}) {
            const colors = ['blue', 'pink', 'green']

            this.eventsSince = startDate;
            this.eventsUntil = endDate;

            await axios
                .get('/events/', {
                    params: {
                        start: startDate,
                        end: endDate,
                        projectId: this.projectId,
                        roomId: this.roomId,
                    }
                })
                .then(response => {
                    this.events = response.data.events
                    this.types = response.data.types
                    this.rooms = response.data.rooms
                    this.projects = response.data.projects

                    // color coding of rooms
                    this.events.map(event => event.class = colors[event.split % colors.length])

                    // fix timezone to current local
                    this.events.map(event => event.start = new Date(event.start))
                    this.events.map(event => event.end = new Date(event.end))

                    this.filterEvents();
                });
        },

        /**
         * Filter Events to decide what to display
         */
        filterEvents() {
            // TODO filter events
            // filter events
            this.displayedEvents = this.events.filter(event =>
                (this.areaFilter.length === 0 || this.areaFilter.find(area => area.id === event.areaId))
                && (this.typeFilter.length === 0 || this.typeFilter.find(type => type.id === event.eventTypeId))
                && (this.roomFilter.length === 0 || this.roomFilter.find(room => room.id === event.roomId))
            )

            this.displayedRooms = this.rooms.filter(room => this.displayedEvents.find(event => event.roomId === room.id))
        },

        /**
         * If the user selects a start, end, and room
         * call the server to get information if there are any collision
         *
         * @returns {Promise<void>}
         */
        async checkCollisions() {
            if (!(this.selectedEvent.start && this.selectedEvent.end && this.selectedEvent.roomId)) {
                this.collision = 0
                return;
            }

            await axios
                .get('/events/collision', {
                    params: {
                        start: this.selectedEvent.start,
                        end: this.selectedEvent.end,
                        roomId: this.selectedEvent.roomId,
                        eventId: this.selectedEvent.id,
                    }
                })
                .then(response => this.collision = response.data);
        },

        /**
         * If the user wants to add a new event by dragging
         * open Modal and fill basic information
         *
         * @param event
         */
        selectEvent(event = null) {
            if (event === null) {
                this.selectedEvent = {
                    projectId: this.projectId,
                    roomId: this.roomId,
                }
                return
            }

            /**
             * Reformat the given JavaScript Date to a ISO 8601 that will work with Input Type dateTime-local
             * Unfortunately the JavaScript toISOString does not convert the timezone,
             * To keep the timezone the offset is subtracted
             * Then the ISOString can be generated, but requires the removal of the trailing Z.
             * Then the Format will work for the HTML Input Type dateTime-local.
             *
             * @example 2021-03-10T01:50:55+0200 => 2021-03-09T23:50:55 (german timezone)
             */
            event.start = event.start.subtractMinutes(event.start.getTimezoneOffset()).toISOString().slice(0, -1)
            event.end = event.end.subtractMinutes(event.end.getTimezoneOffset()).toISOString().slice(0, -1)

            // created by drag and drop
            if (!event.id) {
                this.selectedEvent = {
                    start: event.start,
                    end: event.end,
                    projectId: this.projectId,
                    roomId: event.split ? event.split : this.roomId,
                }
                this.checkCollisions()
                return
            }

            this.selectedEvent = event
            this.collision = event.collisionCount
        },

        /**
         * Updates or creates an event and reloads all events
         *
         * @param event
         * @returns {Promise<*>}
         */
        async updateOrCreateEvent(event) {
            if (event.id) {
                return await axios
                    .put(`/events/${event.id}`, event)
                    .then(response => this.closeModal())
                    .catch(error => this.error = error.response.data.errors);
            }
            return await axios
                .post('/events', event)
                .then(response => this.closeModal())
                .catch(error => this.error = error.response.data.errors);
        },

        async deleteEvent() {
            if (!confirm('are you sure you want to delete this event?')) {
                return;
            }
            return await axios
                .delete(`/events/${this.selectedEvent.id}`)
                .then(response => this.closeModal());
        },

        closeModal() {
            this.selectedEvent = null
            this.error = null;
            this.fetchEvents({startDate: this.eventsSince, endDate: this.eventsUntil});
        },
    }
}
</script>

<style>
/* Styling of Vue Cal */

.vuecal__event {
    font-size: 0.75rem; /* 14px */
    line-height: 1.25rem; /* 20px */
    margin-top: 3px;
}

.vuecal__view-btn {
    font-size: 1rem; /* 16px */
    line-height: 1.5rem; /* 24px */
}

.vuecal__title-bar {
    background-color: #ffffff;
    font-size: 1rem; /* 16px */
    line-height: 1.5rem; /* 24px */
}

.vuecal__split-days-headers {
    font-size: 0.75rem; /* 12px */
    line-height: 1rem; /* 16px */
}

.vuecal__flex.weekday-label {
    font-size: 1rem; /* 12px */
    line-height: 1rem; /* 16px */
    color: #707070;
}

/* Custom Room Colors */

.vuecal__event.blue {
    border: solid rgba(135, 208, 224, 0.9);
    border-width: 0px 0px 0px 3px;
}

.vuecal__event.pink {
    border: solid rgba(209, 130, 211, 0.9);
    border-width: 0px 0px 0px 3px;
}

.vuecal__event.green {
    border: solid rgba(148, 236, 145, 0.9);
    border-width: 0px 0px 0px 3px;
}
</style>
