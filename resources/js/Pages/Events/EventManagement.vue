<template>
    <app-layout title="Event Management">
        <div class="py-4">
            <div class="max-w-screen-lg mb-40 my-12 flex flex-row ml-20 mr-40">
                <div class="flex flex-1 flex-wrap">
                    <div class="w-full flex my-auto justify-between">
                        <div class="flex flex-wrap">
                            <h2 class="text-2xl flex">Raumbelegungen</h2>
                            <button @click="openAddEventModal" type="button"
                                    class="flex my-auto ml-6 items-center border border-transparent rounded-full shadow-sm text-white bg-primary hover:bg-primaryHover focus:outline-none">
                                <PlusSmIcon class="h-5 w-5" aria-hidden="true"/>
                            </button>
                            <div v-if="$page.props.can.show_hints" class="flex mt-1">
                                <SvgCollection svgName="arrowLeft" class="mt-1 ml-2"/>
                                <span
                                    class="font-nanum text-secondary tracking-tight ml-1 my-auto tracking-tight text-lg">Frage neue Raumbelegungen an</span>
                            </div>
                            <div class="bg-stone-50 w-full flex">
                                <div class="mt-14 w-48">
                                    <div v-for="dayDate in month_events[0].days"
                                         class="w-40 inline-flex mt-1 h-36 w-full text-lg text-secondary subpixel-antialiased">
                                        {{ dayDate.date_formatted }}
                                    </div>
                                </div>
                                <div class="flex">
                                    <div v-for="room in month_events" class="inline-flex flex-col">
                                        <h2 class="text-lg text-secondary subpixel-antialiased mt-4 mb-2">
                                            {{ room.name }}
                                        </h2>
                                        <div v-for="day in room.days">
                                            <div v-if="day.events.length > 0" class="bg-white m-0.5 h-36 mr-4 border border-gray-100">
                                                <!-- If only 1 event on that day-->
                                                <div v-if="day.events.length === 1">
                                                    <!-- Icons -->
                                                    <div class="flex p-1 ml-1">
                                                        <UserGroupIcon v-if="day.events[0].audience"
                                                                       class="h-5 w-5 my-auto text-secondary subpixel-antialiased"/>
                                                        <VolumeUpIcon v-if="day.events[0].is_loud" :class="day.events[0].audience ? 'ml-1' : ''"
                                                                      class="h-5 w-5 my-auto text-secondary subpixel-antialiased"/>
                                                        <div v-if="!day.events[0].audience && !day.events[0].is_loud" class="h-5 w-5">

                                                        </div>
                                                    </div>
                                                    <div>
                                                        <!-- Individual Eventname -->
                                                        <div v-if="day.events[0].project_id === null" class="mt-2 ml-2 text-lg flex leading-6 font-bold font-lexend text-primary">
                                                            {{ day.events[0].name }}
                                                        </div>
                                                        <!-- Name of connected Project -->
                                                        <Link :href="route('projects.show',{project: day.events[0].project_id})" v-else class="mt-2 ml-2 text-lg flex leading-6 font-bold font-lexend text-primary">
                                                            {{ projects.data.find(x => x.id === day.events[0].project_id).name}}
                                                        </Link>
                                                        <!-- Time of Event -->
                                                        <div class="ml-2 text-sm text-secondary subpixel-antialiased">
                                                            {{ day.events[0].start_time.split(' ')[1].slice(0, -3) }}
                                                            - {{ day.events[0].end_time.split(' ')[1].slice(0, -3) }}
                                                        </div>
                                                        <!-- EventType -->
                                                        <div class="mt-8 ml-2 mb-1">
                                                            <EventTypeIconCollection :height="20" :width="20"
                                                                                     :iconName="this.event_types.data.find(x => x.id === day.events[0].event_type_id).svg_name"/>
                                                        </div>
                                                    </div>
                                                </div>

                                            </div>
                                            <div @mouseover="activateHover(day.date,room.id)" @click="openAddEventModal(room.id)"
                                                 @mouseout="deactivateHover()" v-else class="m-0.5 h-36 mr-4 w-44 flex cursor-pointer" :class="showAddHoverDate === day.date && showAddHoverRoomId === room.id ? 'bg-secondary' : ''">
                                                <button v-show="showAddHoverDate === day.date && showAddHoverRoomId === room.id" type="button"
                                                        class="m-auto border border-transparent rounded-full shadow-sm text-white bg-primary bg-primaryHover focus:outline-none">
                                                    <PlusSmIcon  class="h-6 w-6" aria-hidden="true"/>
                                                </button>

                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <pre>
                                {{ month_events }}
                            </pre>
                            </div>
                        </div>
                    </div>

                </div>


            </div>
        </div>
        <!-- Termin erstellen Modal-->
        <jet-dialog-modal :show="addingEvent" @close="closeAddEventModal">
            <template #content>
                <div class="mx-4">
                    <div class="font-bold font-lexend text-primary tracking-wide text-2xl my-2">
                        Neue Raumbelegung
                    </div>
                    <XIcon @click="closeAddEventModal" class="h-5 w-5 right-0 top-0 mt-8 mr-5 absolute cursor-pointer"
                           aria-hidden="true"/>
                    <div class="text-secondary subpixel-antialiased">
                        Bitte beachte, dass du Vor- und Nachbereitungszeit einplanst.
                    </div>
                    <div v-if="$page.props.can.show_hints" class="mt-6 ml-4 flex">
                        <SvgCollection svgName="arrowLeft" class="mt-3 ml-2 flex-shrink-0"/>
                        <span
                            class="font-nanum text-secondary tracking-tight ml-1 my-auto tracking-tight text-lg">Hier kannst du die Art des Termins definieren. ihn einem Projekt zuordnen und weitere Infos mit deinem Team teilen. Anschließend kannst du dafür die Raumbelegung anfragen.</span>
                    </div>
                    <div class="flex justify-between">
                        <Listbox as="div" class="flex" v-model="selectedEventType">
                            <div class="relative">
                                <ListboxButton
                                    class="bg-white w-56 relative mt-6 font-semibold py-2 text-left cursor-pointer focus:outline-none sm:text-sm">
                                    <div class="flex items-center my-auto">
                                        <EventTypeIconCollection :height="20" :width="20"
                                                                 :iconName="selectedEventType.svg_name"/>
                                        <span class="block truncate items-center ml-3 flex">
                                            <span>{{ selectedEventType.name }}</span>
                                </span>
                                        <span
                                            class="absolute inset-y-0 right-0 flex items-center pr-2 pointer-events-none">
                                     <ChevronDownIcon class="h-5 w-5 text-gray-400" aria-hidden="true"/>
                                </span>
                                    </div>
                                </ListboxButton>

                                <transition leave-active-class="transition ease-in duration-100"
                                            leave-from-class="opacity-100" leave-to-class="opacity-0">
                                    <ListboxOptions
                                        class="absolute w-56 z-10 mt-1 bg-primary shadow-lg max-h-32 pl-1 pr-2 pt-2 pb-2 text-base ring-1 ring-black ring-opacity-5 overflow-y-auto focus:outline-none sm:text-sm">
                                        <ListboxOption as="template" class="max-h-8"
                                                       v-for="eventType in event_types.data"
                                                       :key="eventType.name"
                                                       :value="eventType"
                                                       v-slot="{ active, selected }">
                                            <li :class="[active ? 'bg-primaryHover text-white' : 'text-secondary', 'group cursor-pointer flex items-center justify-between py-2 pl-3 pr-9 text-sm subpixel-antialiased']">
                                                <EventTypeIconCollection :height="20" :width="20"
                                                                         :iconName="eventType.svg_name"/>
                                                <span
                                                    :class="[selected ? 'font-bold text-white' : 'font-normal', 'block truncate']">
                                                        {{ eventType.name }}
                                                    </span>
                                                <span
                                                    :class="[active ? 'bg-primaryHover text-white' : 'text-secondary', 'group flex items-center text-sm subpixel-antialiased']">
                                                      <CheckIcon v-if="selected" class="h-5 w-5 flex text-success"
                                                                 aria-hidden="true"/>
                                                </span>
                                            </li>
                                        </ListboxOption>
                                    </ListboxOptions>
                                </transition>
                            </div>
                        </Listbox>
                        <Listbox as="div" class="flex" v-model="selectedRoom">
                            <div class="relative">
                                <ListboxButton
                                    class="bg-white w-56 relative mt-6 font-semibold py-2 text-left cursor-pointer focus:outline-none sm:text-sm">
                                    <div class="flex items-center my-auto">
                                        <span v-if="selectedRoom" class="block truncate items-center flex">
                                            <span>{{ selectedRoom.name }}</span>

                                        </span>
                                        <span v-if="!selectedRoom"
                                              class="block truncate">Raum definieren*</span>
                                        <span
                                            class="absolute inset-y-0 right-0 flex items-center pr-2 pointer-events-none">
                                            <ChevronDownIcon class="h-5 w-5 text-gray-400" aria-hidden="true"/>
                                         </span>
                                    </div>
                                </ListboxButton>
                                <transition leave-active-class="transition ease-in duration-100"
                                            leave-from-class="opacity-100" leave-to-class="opacity-0">
                                    <ListboxOptions
                                        class="absolute w-56 z-10 mt-1 bg-primary shadow-lg max-h-64 p-3 text-base ring-1 ring-black ring-opacity-5 overflow-y-auto focus:outline-none sm:text-sm">
                                        <div v-for="area in areas.data">
                                            <p class="text-secondary mt-1 text-sm uppercase ml-3 subpixel-antialiased cursor-pointer">
                                                {{ area.name }}</p>
                                            <ListboxOption as="template" class="max-h-8"
                                                           v-for="room in area.rooms"
                                                           :key="room.name"
                                                           :value="room"
                                                           v-slot="{ active, selected }">
                                                <li :class="[active ? 'bg-primaryHover text-white' : 'text-secondary', 'group cursor-pointer flex items-center justify-between py-2 pl-3 pr-9 text-sm subpixel-antialiased']">
                                                <span
                                                    :class="[selected ? 'font-bold text-white' : 'font-normal', 'block truncate']">
                                                        {{ room.name }}
                                                    </span>
                                                    <span
                                                        :class="[active ? 'bg-primaryHover text-white' : 'text-secondary', 'group flex items-center text-sm subpixel-antialiased']">
                                                      <CheckIcon v-if="selected" class="h-5 w-5 flex text-success"
                                                                 aria-hidden="true"/>
                                                </span>
                                                </li>
                                            </ListboxOption>
                                        </div>
                                    </ListboxOptions>
                                </transition>
                            </div>
                        </Listbox>
                    </div>
                    <div class="flex mt-6">
                        <input v-model="assignProject"
                               type="checkbox"
                               class="ring-offset-0 cursor-pointer focus:ring-0 focus:shadow-none h-6 w-6 text-success border-2 border-gray-300"/>
                        <p :class="[assignProject ? 'text-primary font-black' : 'text-secondary', 'subpixel-antialiased']"
                           class="ml-4 my-auto text-sm">Termin einem Projekt zuordnen</p>
                    </div>
                    <div v-if="assignProject">
                        <div class="flex items-center mt-4">
                            <Switch v-model="creatingProject"
                                    :class="[creatingProject ?
                                        'bg-success' :
                                        'bg-gray-300',
                                        'relative inline-flex flex-shrink-0 h-3 w-6 border-2 border-transparent rounded-full cursor-pointer transition-colors ease-in-out duration-200 focus:outline-none']">
                                <span aria-hidden="true"
                                      :class="[creatingProject ? 'translate-x-3' : 'translate-x-0', 'pointer-events-none inline-block h-2 w-2 rounded-full bg-white shadow transform ring-0 transition ease-in-out duration-200']"/>
                            </Switch>
                            <span class="ml-4 text-sm"
                                  :class="[creatingProject ? 'text-primary font-black' : 'text-secondary', 'subpixel-antialiased']">
                                Neues Projekt
                            </span>
                            <div v-if="$page.props.can.show_hints" class="ml-3 flex">
                                <SvgCollection svgName="arrowLeft" class="mt-1 flex-shrink-0"/>
                                <span
                                    class="font-nanum text-secondary tracking-tight ml-1 my-auto tracking-tight text-lg">Lege gleichzeitig ein neues Projekt an</span>
                            </div>
                        </div>
                        <div class="mt-2 flex flex-wrap" v-if="!creatingProject">
                            <div class="my-auto w-full" v-if="this.selectedProject === null">
                                <input id="projectSearch" v-model="project_query" type="text" autocomplete="off" @focusout="project_query = ''"
                                       class="text-primary h-10 focus:border-black border-2 w-full text-sm border-gray-300 "
                                       placeholder="Zu welchem bestehendem Projekt zuordnen?*" :disabled="this.selectedProject"/>
                                <transition leave-active-class="transition ease-in duration-100"
                                            leave-from-class="opacity-100"
                                            leave-to-class="opacity-0">
                                    <div v-if="project_search_results.length > 0 && project_query.length > 0"
                                         class="absolute z-10 w-full max-h-60 bg-primary shadow-lg
                                         text-base ring-1 ring-black ring-opacity-5
                                         overflow-auto focus:outline-none sm:text-sm">
                                        <div class="border-gray-200">
                                            <div v-for="(project, index) in project_search_results" :key="index"
                                                 class="flex items-center cursor-pointer">
                                                <div class="flex-1 text-sm py-4">
                                                    <p @click="addProjectToEvent(project)"
                                                       class="font-bold px-4 text-white hover:border-l-4 hover:border-l-success">
                                                        {{ project.name }}
                                                    </p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </transition>
                            </div>

                            <div>

                            <span v-if="this.selectedProject !== null" class="flex mt-4 mr-1 rounded-full items-center font-bold text-primary">
                            <div class="flex items-center">
                                <span class="flex">
                                    Aktuell zugeordnet zu: {{ this.selectedProject.name}}
                                </span>
                            </div>
                            <button type="button" @click="deleteSelectedProject()">
                                <span class="sr-only">User aus Team entfernen</span>
                                <XCircleIcon class="ml-2 mt-1 h-5 w-5 hover:text-error "/>
                            </button>
                            </span>
                            </div>
                        </div>
                        <div class="flex mt-2" v-if="creatingProject">
                            <input type="text" v-model="addProjectForm.name"
                                   placeholder="Projektname von neuem Projekt*"
                                   class="text-primary h-10 focus:border-black border-2 w-full text-sm border-gray-300 "/>
                        </div>
                    </div>
                    <div class="mt-4">
                        <input type="text" v-model="addEventForm.name" placeholder="Terminname"
                               class="text-primary h-10 focus:border-black border-2 w-full text-sm border-gray-300 "/>
                    </div>
                    <div class="flex mt-4">
                        <div class="text-secondary mr-2">
                            <label for="startDate">Terminstart*</label>
                            <input
                                v-model="addEventForm.start_time" id="startDate"
                                placeholder="Terminstart" type="datetime-local"
                                class="border-gray-300 text-primary placeholder-secondary mr-2 w-full"/>
                        </div>
                        <div class="text-secondary ml-2">
                            <label for="endDate">Terminende*</label>
                            <input
                                v-model="addEventForm.end_time" id="endDate"
                                placeholder="Zu erledigen bis?" type="datetime-local"
                                class="border-gray-300 text-primary placeholder-secondary w-full"/>
                        </div>
                    </div>
                    <div class="flex mt-4 items-center justify-between">
                        <div class="flex">
                            <input v-model="addEventForm.occupancy_option"
                                   type="checkbox"
                                   class="ring-offset-0 cursor-pointer focus:ring-0 focus:shadow-none h-6 w-6 text-success border-2 border-gray-300"/>
                            <AdjustmentsIcon class="h-5 w-5 ml-2 my-auto"
                                             :class="[addEventForm.occupancy_option ? 'text-primary font-black' : 'text-secondary', 'subpixel-antialiased']"/>
                            <p :class="[addEventForm.occupancy_option ? 'text-primary font-black' : 'text-secondary', 'subpixel-antialiased']"
                               class="ml-1 my-auto text-sm">Belegungsoption</p>
                        </div>
                        <div class="flex items-center justify-between">
                            <input v-model="addEventForm.audience"
                                   type="checkbox"
                                   class="ring-offset-0 cursor-pointer focus:ring-0 focus:shadow-none h-6 w-6 text-success border-2 border-gray-300"/>
                            <UserGroupIcon class="h-5 w-5 ml-2 my-auto"
                                           :class="[addEventForm.audience ? 'text-primary font-black' : 'text-secondary', 'subpixel-antialiased']"/>
                            <p :class="[addEventForm.audience ? 'text-primary font-black' : 'text-secondary', 'subpixel-antialiased']"
                               class="ml-1 my-auto text-sm">Publikum</p>
                        </div>
                        <div class="flex justify-between">
                            <input v-model="addEventForm.is_loud"
                                   type="checkbox"
                                   class="ring-offset-0 cursor-pointer focus:ring-0 focus:shadow-none h-6 w-6 text-success border-2 border-gray-300"/>
                            <VolumeUpIcon class="h-5 w-5 ml-2 my-auto"
                                          :class="[addEventForm.is_loud ? 'text-primary font-black' : 'text-secondary', 'subpixel-antialiased']"/>
                            <p :class="[addEventForm.is_loud ? 'text-primary font-black' : 'text-secondary', 'subpixel-antialiased']"
                               class="ml-1 my-auto text-sm">Es wird laut</p>
                        </div>
                    </div>
                    <div class="mt-4">
                        <textarea placeholder="Was gibt es bei dem Termin zu beachten?"
                                  v-model="addEventForm.description" rows="4"
                                  class="resize-none shadow-sm placeholder-secondary p-4 focus:ring-black focus:border-black border-2 block w-full sm:text-sm border border-gray-300"/>
                    </div>
                    <div>
                        <button :class="[this.addEventForm.start_time === null || this.addEventForm.end_time === null || this.selectedRoom === null ?
                    'bg-secondary': 'bg-primary hover:bg-primaryHover focus:outline-none']"
                                class="mt-4 flex items-center px-20 py-3 border border-transparent
                            text-base font-bold uppercase shadow-sm text-secondaryHover"
                                @click="addEvent"
                                :disabled="addEventForm.start_time === null && addEventForm.end_time === null">
                            Belegen
                        </button>
                    </div>
                </div>
            </template>
        </jet-dialog-modal>
    </app-layout>
</template>
<script>

import {defineComponent} from 'vue'
import AppLayout from '@/Layouts/AppLayout.vue'
import {
    DotsVerticalIcon,
    ChevronDownIcon,
    XIcon,
    AdjustmentsIcon,
    UserGroupIcon,
    VolumeUpIcon
} from '@heroicons/vue/outline'
import {ChevronUpIcon, PlusSmIcon, CheckIcon, XCircleIcon} from '@heroicons/vue/solid'

import {
    Listbox,
    ListboxButton, ListboxLabel,
    ListboxOption,
    ListboxOptions,
    Menu,
    MenuButton,
    MenuItem, MenuItems, Switch
} from '@headlessui/vue'
import Button from "@/Jetstream/Button";
import JetButton from "@/Jetstream/Button";
import JetDialogModal from "@/Jetstream/DialogModal";
import JetInput from "@/Jetstream/Input";
import JetInputError from "@/Jetstream/InputError";
import JetSecondaryButton from "@/Jetstream/SecondaryButton";
import Checkbox from "@/Layouts/Components/Checkbox";
import {useForm} from "@inertiajs/inertia-vue3";
import SvgCollection from "@/Layouts/Components/SvgCollection";
import {Link} from "@inertiajs/inertia-vue3";
import EventTypeIconCollection from "@/Layouts/Components/EventTypeIconCollection";



export default defineComponent({
    components: {
        ListboxLabel,
        SvgCollection,
        Button,
        AppLayout,
        DotsVerticalIcon,
        PlusSmIcon,
        CheckIcon,
        Menu,
        MenuButton,
        MenuItem,
        MenuItems,
        JetButton,
        JetDialogModal,
        JetInput,
        JetInputError,
        JetSecondaryButton,
        ChevronDownIcon,
        ChevronUpIcon,
        Checkbox,
        XIcon,
        XCircleIcon,
        Listbox,
        ListboxButton,
        ListboxOption,
        ListboxOptions,
        Link,
        EventTypeIconCollection,
        AdjustmentsIcon,
        UserGroupIcon,
        VolumeUpIcon,
        Switch
    },
    props: ['optional_events', 'event_types', 'areas', 'month_events', 'day_events','projects'],
    methods: {
        openAddEventModal(roomId) {
            this.addingEvent = true;
            if(this.showAddHoverDate !== null){
                const startDate = new Date(this.showAddHoverDate);
                startDate.setMinutes(startDate.getMinutes() - 1200);
                this.addEventForm.start_time = startDate.toISOString().slice(0,16);

                const endDate = new Date(this.showAddHoverDate);
                endDate.setMinutes(endDate.getMinutes() + 239);
                this.addEventForm.end_time = endDate.toISOString().slice(0,16);
            }
            if(roomId !== null){
                this.areas.data.forEach((area) => {
                    area.rooms.forEach((room) => {
                        if(room.id === roomId){
                            this.selectedRoom = room;
                        }
                    })

                })
            }
        },
        closeAddEventModal() {
            this.addingEvent = false;
            this.addEventForm.eventType = null;
            this.addEventForm.name = '';
            this.addEventForm.start_time = null;
            this.addEventForm.end_time = null;
            this.addEventForm.description = '';
            this.addEventForm.occupancy_option = false;
            this.addEventForm.is_loud = false;
            this.addEventForm.audience = false;
            this.selectedRoom = null;
            this.addEventForm.project = null;
            this.selectedEventType = this.event_types.data[0];
        },
        addEvent() {
            this.addEventForm.event_type_id = this.selectedEventType.id;
            this.addEventForm.room_id = this.selectedRoom.id;
            if (this.assignProject) {
                if(this.creatingProject){
                    this.addEventForm.project_id = null;
                    this.newProject = this.addProjectForm.post(route('projects.store'), {})
                    this.addEventForm.project_id = this.newProject.id;
                }else if(this.selectedProject != null){
                    this.addEventForm.project_id = this.selectedProject.id;
                }
            }

            this.addEventForm.post(route('events.store'), {});
            this.closeAddEventModal();
        },
        addProjectToEvent(project){
            this.selectedProject = project;
            this.project_query = ""
        },
        deleteSelectedProject(){
            this.selectedProject = null;
        },
        activateHover(date,roomId){
            this.showAddHoverDate = date;
            this.showAddHoverRoomId = roomId;
        },
        deactivateHover(){
            this.showAddHoverDate = null;
            this.showAddHoverRoomId = null;
        }
    },
    watch: {
        project_query: {
            handler() {
                if (this.project_query.length > 0) {
                    axios.get('/projects/search', {
                        params: {query: this.project_query}
                    }).then(response => {
                        this.project_search_results = response.data
                    })
                }
            },
            deep: true
        }
    },
    data() {
        return {
            addingEvent: false,
            selectedEventType: this.event_types.data[0],
            assignProject: false,
            selectedProject: null,
            showAddHoverDate: null,
            showAddHoverRoomId: null,
            newProject:null,
            selectedRoom: null,
            creatingProject: false,
            project_query: "",
            project_search_results: [],
            addEventForm: useForm({
                name: '',
                start_time: null,
                end_time: null,
                description: '',
                occupancy_option: false,
                is_loud: false,
                audience: false,
                room_id: null,
                project_id: null,
                event_type_id: null,
                user_id: this.$page.props.user.id,
            }),
            addProjectForm: useForm({
                name: '',
            })
        }
    },
})
</script>
