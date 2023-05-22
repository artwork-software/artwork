<template>
    <jet-dialog-modal :show="true" @close="closeModal(false)">
        <template #content>
            <img v-if="!this.event?.id" alt="Neuer Termin" src="/Svgs/Overlays/illu_appointment_new.svg"
                 class="-ml-6 -mt-8 mb-4"/>
            <img v-else alt="Belegung kommentieren" src="/Svgs/Overlays/illu_appointment_edit.svg"
                 class="-ml-6 -mt-8 mb-4"/>
            <XIcon @click="closeModal(false)" class="text-secondary h-5 w-5 right-0 top-0 mt-8 mr-5 absolute cursor-pointer"
                   aria-hidden="true"/>
            <div class="mx-4">
                <!--   Heading   -->
                <div>
                    <h1 class="my-1 flex">
                        <div class="flex-grow headline1">
                            Belegung kommentieren
                        </div>
                    </h1>
                    <div class="flex items-center">
                        erstellt von <img v-if="this.event.created_by" :data-tooltip-target="this.event.created_by.id"
                                          :src="this.event.created_by.profile_photo_url"
                                          :alt="this.event.created_by.last_name"
                                          class="ml-2 my-auto ring-white ring-2 rounded-full h-7 w-7 object-cover"/>
                        <div class="xsLight ml-3" v-else>
                            gelöschte Nutzer:in
                        </div>
                    </div>
                </div>
                <!--    Form    -->
                <!--    Type and Title    -->
                <div class="flex py-2">
                    <div class="w-1/2">
                        <div class=" w-full flex cursor-pointer truncate" v-if="!canEdit || this.disableEventTypeSelector">
                            <EventTypeIconCollection :height="40" :width="40"
                                                     :iconName="selectedEventType?.svg_name"/>
                            <p class="ml-2 flex items-center text-lg font-lexend font-semibold">
                                {{ selectedEventType?.name }}
                            </p>
                        </div>
                        <Listbox as="div" class="flex h-12 mr-2" v-model="selectedEventType" v-if="canEdit && !this.disableEventTypeSelector"
                                 id="eventType">
                            <ListboxButton
                                class="pl-3 h-12 inputMain w-full bg-white relative font-semibold py-2 text-left cursor-pointer focus:outline-none sm:text-sm">
                                <div class="flex items-center my-auto">
                                    <EventTypeIconCollection :height="20" :width="20"
                                                             :iconName="selectedEventType?.svg_name"/>
                                    <span class="block truncate items-center ml-3 flex">
                                            <span>{{ selectedEventType?.name }}</span>
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
                                    class="absolute w-72 z-10 mt-12 bg-primary shadow-lg max-h-32 pr-2 pt-2 pb-2 text-base ring-1 ring-black ring-opacity-5 overflow-y-scroll focus:outline-none sm:text-sm">
                                    <ListboxOption as="template" class="max-h-8"
                                                   v-for="eventType in eventTypes"
                                                   :key="eventType.name"
                                                   :value="eventType"
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
                        <p class="text-xs text-red-800">{{ error?.eventType?.join('. ') }}</p>
                    </div>

                    <div class="w-1/2" v-if="canEdit">
                        <input type="text"
                               v-model="this.eventName"
                               id="eventTitle"
                               :placeholder="selectedEventType?.individual_name ? 'Terminname*' : 'Terminname'"
                               :disabled="!canEdit"
                               class="h-12 sDark inputMain placeholder:xsLight placeholder:subpixel-antialiased focus:outline-none focus:ring-0 focus:border-secondary focus:border-1 w-full border-gray-300"/>

                        <p v-if="selectedEventType?.individual_name" class="text-xs text-red-800">
                            {{ error?.eventName?.join('. ') }}</p>
                    </div>
                    <div v-else class="flex w-1/2 ml-12 items-center">
                        {{ this.eventName }}
                    </div>
                </div>
                <!-- Attribute Menu -->
                <Menu as="div" class="inline-block text-left w-full" v-if="canEdit">
                    <div>
                        <MenuButton
                            class="h-12 inputMain w-full bg-white px-4 py-2 text-sm font-medium text-black focus:outline-none focus-visible:ring-2 focus-visible:ring-white "
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
                            class="absolute overflow-y-auto h-24 mt-2 w-[88%] origin-top-left divide-y divide-gray-200 rounded-sm bg-primary ring-1 ring-black p-2 text-white opacity-100 z-50">
                            <div class="mx-auto w-full rounded-2xl bg-primary border-none mt-2">
                                <div class="flex w-full mb-4">
                                    <input v-model="audience"
                                           :disabled="!canEdit"
                                           type="checkbox"
                                           class="cursor-pointer h-6 w-6 text-success border-2 border-gray-300 focus:ring-0"/>
                                    <img src="/Svgs/IconSvgs/icon_public.svg" class="h-6 w-6 mx-2" alt="audienceIcon"/>

                                    <div :class="[audience ? 'xsWhiteBold' : 'xsLight', 'my-auto']">
                                        Mit Publikum
                                    </div>
                                </div>
                                <div class="flex w-full mb-2">
                                    <input v-model="isLoud"
                                           :disabled="!canEdit"
                                           type="checkbox"
                                           class="cursor-pointer h-6 w-6 text-success border-2 border-gray-300 focus:ring-0"/>
                                    <div :class="[isLoud ? 'xsWhiteBold' : 'xsLight', 'my-auto mx-2']">Es
                                        wird laut
                                    </div>
                                </div>
                            </div>
                        </MenuItems>
                    </transition>
                </Menu>
                <div v-if="!canEdit" class="flex w-full">
                    <div class="w-1/2 flex items-center my-auto" v-if="this.selectedProject?.id">
                        Zugeordnet zu: <a
                        :href="route('projects.show', {project: selectedProject.id, openTab: 'calendar'})"
                        class="ml-3 mt-1 text-sm items-center flex font-bold font-lexend text-primary">
                        {{ this.selectedProject?.name }}
                    </a>
                    </div>
                    <div class="flex items-center w-1/2">
                        <p class="truncate xsLight subpixel-antialiased max-w-60">
                            erstellt von {{ this.event.created_by.first_name }}
                            {{ this.event.created_by.last_name }}</p> <img
                        :data-tooltip-target="this.event.created_by.id" :src="this.event.created_by.profile_photo_url"
                        :alt="this.event.created_by.last_name"
                        class="ml-4 ring-white ring-2 rounded-full h-9 w-9 object-cover"/>
                    </div>
                </div>
                <div v-if="!canEdit" class="my-2">
                    <div v-if="this.startDate === this.endDate">
                        {{ this.selectedRoom?.name }},
                        {{ this.startDate.toString().substring(10, 8) }}.{{
                            this.startDate.toString().substring(7, 5)
                        }}.{{ this.startDate.toString().substring(4, 0) }},
                        {{ this.startTime }} - {{ this.endTime }}
                    </div>
                    <div v-else>
                        {{ this.selectedRoom?.name }},
                        {{ this.startDate.toString().substring(10, 8) }}.{{
                            this.startDate.toString().substring(7, 5)
                        }}.{{ this.startDate.toString().substring(4, 0) }},
                        {{ this.startTime }} -
                        {{ this.endDate.toString().substring(10, 8) }}.{{
                            this.endDate.toString().substring(7, 5)
                        }}.{{ this.endDate.toString().substring(4, 0) }},
                        {{ this.endTime }}
                    </div>
                </div>
                <!--    Properties    -->
                <div class="flex py-2">
                    <div v-if="audience">
                        <TagComponent icon="audience" displayed-text="Mit Publikum" hideX="true"></TagComponent>
                    </div>
                    <div v-if="isLoud">
                        <TagComponent displayed-text="es wird laut" hideX="true"></TagComponent>
                    </div>
                </div>
                <!--    Project    -->
                <div v-if="canEdit">
                    <div class="xsLight flex" v-if="!this.creatingProject">
                        Aktuell zugeordnet zu:
                        <a v-if="this.selectedProject?.id"
                           :href="route('projects.show', {project: selectedProject.id, openTab: 'calendar'})"
                           class="ml-3 flex xsDark">
                            {{ this.selectedProject?.name }}
                        </a>
                        <div v-else class="xsDark ml-2">
                            {{ this.selectedProject?.name ?? 'Keinem Projekt' }}
                        </div>
                        <div v-if="this.selectedProject?.id && this.canEdit" class="flex items-center my-auto">
                            <button type="button"
                                    @click="selectedProject = null">
                                <XCircleIcon class="pl-2 h-6 w-6 hover:text-error text-primary"/>
                            </button>
                        </div>
                    </div>
                    <div class="xsLight" v-if="this.creatingProject">
                        Das Projekt wird beim Abspeichern erstellt.
                    </div>

                    <div class="my-2" v-if="this.canEdit">
                        <div class="flex pb-2">
                            <span class="mr-4 "
                                  :class="[!creatingProject ? 'xsDark' : 'xsLight',]">
                                Bestehendes Projekt
                            </span>
                            <div class="flex">
                                <label for="project-toggle" class="inline-flex relative items-center cursor-pointer">
                                    <input type="checkbox"
                                           v-model="creatingProject"
                                           :disabled="!canEdit"
                                           id="project-toggle"
                                           class="sr-only peer">
                                    <div class="w-9 h-5 bg-gray-200 rounded-full
                            peer-checked:after:translate-x-full peer-checked:after:border-white
                            after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300
                            after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-buttonBlue">
                                    </div>
                                </label>
                                <span class="ml-4 text-sm"
                                      :class="[creatingProject ? 'xsDark' : 'xsLight']">
                                Neues Projekt
                            </span>
                                <div v-if="showHints" class="ml-3 flex">
                                    <SvgCollection svgName="arrowLeft" class="mt-1"/>
                                    <div class=" ml-1 my-auto hind">
                                        Lege gleichzeitig ein neues Projekt an
                                    </div>
                                </div>
                            </div>

                        </div>
                        <input type="text"
                               :placeholder="creatingProject ? 'Neuer Projektname' : 'Projekt suchen'"
                               v-model="projectName"
                               class="h-10 inputMain placeholder:xsLight placeholder:subpixel-antialiased focus:outline-none focus:ring-0 focus:border-secondary focus:border-1 w-full border-gray-300"/>

                        <div v-if="projectSearchResults.length > 0 && !creatingProject"
                             class="absolute bg-primary truncate sm:text-sm w-10/12">
                            <div v-for="(project, index) in projectSearchResults"
                                 :key="index"
                                 @click="chooseProject(project)"
                                 class="p-4 xsWhiteBold border-l-4 hover:border-l-success border-l-primary cursor-pointer">
                                {{ project.name }}
                            </div>
                        </div>

                        <p class="text-xs text-red-800">{{ error?.projectId?.join('. ') }}</p>
                        <p class="text-xs text-red-800">{{ error?.projectName?.join('. ') }}</p>
                    </div>
                </div>

                <!--    Time    -->
                <div v-if="canEdit" class="flex pb-1 flex-col sm:flex-row align-baseline">
                    <div class="sm:w-1/2">
                        <label for="startDate" class="xxsLight">Start</label>
                        <div class="w-full flex">
                            <input v-model="startDate"
                                   id="startDate"
                                   @change="checkChanges()"
                                   type="date"
                                   :disabled="!canEdit"
                                   required
                                   class="border-gray-300 inputMain xsDark placeholder-secondary disabled:border-none flex-grow"/>
                            <input v-model="startTime"
                                   id="changeStartTime"
                                   @change="checkChanges()"
                                   type="time"
                                   :disabled="!canEdit"
                                   required
                                   class="border-gray-300 inputMain xsDark placeholder-secondary  disabled:border-none"/>
                        </div>
                        <p class="text-xs text-red-800">{{ error?.start?.join('. ') }}</p>
                    </div>
                    <div class="sm:w-1/2">
                        <label for="endDate" class="xxsLight">Ende</label>
                        <div class="w-full flex">
                            <input v-model="endDate"
                                   id="endDate"
                                   @change="checkChanges()"
                                   type="date"
                                   required
                                   :disabled="!canEdit"
                                   class="border-gray-300 inputMain xsDark placeholder-secondary  disabled:border-none flex-grow"/>
                            <input v-model="endTime"
                                   id="changeEndTime"
                                   @change="checkChanges()"
                                   type="time"
                                   required
                                   :disabled="!canEdit"
                                   class="border-gray-300 inputMain xsDark placeholder-secondary  disabled:border-none"/>
                        </div>
                        <p class="text-xs text-red-800">{{ error?.end?.join('. ') }}</p>
                    </div>

                </div>
                <div>
                    <div class="text-red-500 text-xs" v-show="helpTextLength.length > 0">{{ helpTextLength }}</div>
                </div>

                <!--    Room    -->
                <div class="py-1" v-if="canEdit">
                    <Listbox as="div" v-model="selectedRoom" id="room" v-if="canEdit && selectedRoom">
                        <ListboxButton class="inputMain w-full h-10 cursor-pointer truncate flex p-2">
                            <div class="flex-grow flex text-left xsDark">
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
                                <div :class="[selected ? 'xsWhiteBold' : 'xsLight', 'flex']">
                                    {{ room.name }} <img v-if="this.roomCollisions ? this.roomCollisions[room.id] > 0 : false"
                                                         src="/Svgs/IconSvgs/icon_warning_white.svg"
                                                         class="h-4 w-4 mx-2" alt="conflictIcon"/>
                                </div>
                                <CheckIcon v-if="selected" class="h-5 w-5 text-success" aria-hidden="true"/>
                            </ListboxOption>
                        </ListboxOptions>
                    </Listbox>
                    <Listbox as="div" v-model="selectedRoom" id="room" v-else>
                        <ListboxButton class="inputMain w-full h-10 cursor-pointer truncate flex p-2">
                            <div class="flex-grow xsLight text-left subpixel-antialiased">
                                Raum wählen*
                            </div>
                            <ChevronDownIcon class="h-5 w-5 text-primary" aria-hidden="true"/>
                        </ListboxButton>
                        <ListboxOptions class="w-[88%] bg-primary max-h-32 overflow-y-auto text-sm absolute">
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
                <!--    Description    -->
                <div class="py-2">
                    <textarea v-if="canEdit" placeholder="Was gibt es bei dem Termin zu beachten?"
                              id="description"
                              :disabled="!canEdit"
                              v-model="description"
                              rows="4"
                              class="inputMain resize-none w-full xsDark placeholder:xsLight placeholder:subpixel-antialiased focus:outline-none focus:ring-0 focus:border-secondary focus:border-1 w-full border-gray-300"/>
                    <div v-else-if="this.description" class="mt-4 xsDark">
                        {{ this.description }}
                    </div>
                </div>
                <div v-if="showComments" class="my-6" v-for="comment in this.event.comments">
                    <div class="flex justify-between">
                        <div class="flex items-center">
                            <NewUserToolTip :id="comment.id" :user="comment.user" :height="8"
                                            :width="8"></NewUserToolTip>
                            <div class="ml-2 text-secondary">
                                {{ comment.created_at }}
                            </div>
                        </div>
                    </div>
                    <div class="mt-2 mr-14 subpixel-antialiased text-primary">
                        {{ comment.comment }}
                    </div>
                </div>
                <div class="mt-6">
                        <textarea v-if="canEdit" placeholder="Deine Antwort eingeben"
                                  id="newComment"
                                  :disabled="!canEdit"
                                  v-model="newComment"
                                  rows="4"
                                  class="inputMain resize-none w-full xsDark placeholder:xsLight placeholder:subpixel-antialiased focus:outline-none focus:ring-0 focus:border-secondary focus:border-1 border-gray-300"/>
                </div>
                <div>
                    <div class="flex justify-center w-full py-4">
                        <button :disabled="this.selectedRoom === null || endDate > seriesEndDate || series && !seriesEndDate || newComment === ''"
                                :class="this.selectedRoom === null || endDate > seriesEndDate || series && !seriesEndDate || this.startTime === null || this.startDate === null || this.endTime === null || this.endDate === null || newComment === '' ? 'bg-secondary hover:bg-secondary' : ''"
                                class="bg-buttonBlue hover:bg-indigo-600 py-2 px-8 rounded-full text-white"
                                @click="updateAndAnswerEvent()">
                            Antwort senden
                        </button>
                    </div>
                </div>
            </div>
        </template>
    </jet-dialog-modal>
</template>


<script>

import {ref} from "vue";

import JetDialogModal from "@/Jetstream/DialogModal";
import {ChevronDownIcon, DotsVerticalIcon, PencilAltIcon, XCircleIcon, XIcon} from '@heroicons/vue/outline';
import EventTypeIconCollection from "@/Layouts/Components/EventTypeIconCollection";
import {
    Listbox,
    ListboxButton, ListboxLabel,
    ListboxOption,
    ListboxOptions,
    Menu,
    MenuButton,
    MenuItem,
    MenuItems, Switch, SwitchGroup, SwitchLabel
} from "@headlessui/vue";
import {CheckIcon, ChevronUpIcon, TrashIcon} from "@heroicons/vue/solid";
import SvgCollection from "@/Layouts/Components/SvgCollection";
import Input from "@/Jetstream/Input";
import ConfirmationComponent from "@/Layouts/Components/ConfirmationComponent";
import TagComponent from "@/Layouts/Components/TagComponent";
import InputComponent from "@/Layouts/Components/InputComponent";
import {useForm} from "@inertiajs/inertia-vue3";
import ChangeAllSubmitModal from "@/Layouts/Components/ChangeAllSubmitModal.vue";
import NewUserToolTip from "@/Layouts/Components/NewUserToolTip.vue";
import dayjs from "dayjs";
import Permissions from "@/mixins/Permissions.vue";

export default {
    name: 'EventComponent',
    mixins: [Permissions],
    components: {
        NewUserToolTip,
        ChangeAllSubmitModal,
        ListboxLabel,
        SwitchLabel,
        Switch,
        SwitchGroup,
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
        TagComponent,
        InputComponent,
    },
    data() {
        return {
            helpTextLength: '',
            startDate: null,
            startTime: null,
            endDate: null,
            endTime: null,
            isLoud: false,
            audience: false,
            showSeriesEdit: false,
            allSeriesEvents: false,
            frequencies: [
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
            ],
            series: false,
            seriesEndDate: null,
            selectedFrequency: {
                id: 2,
                name: 'Wöchentlich'
            } ,
            projectName: null,
            title: null,
            isOption: true,
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
            canEdit: null,
            declinedRoomId: null,
            deleteComponentVisible: false,
            adminComment: '',
            optionString: null,
            accept: true,
            optionAccept: false,
            disableEventTypeSelector: false,
            answerRequestForm: useForm({
                accepted: false,
            }),
            collisionRoomForm: this.$inertia.form({
                _method: 'POST',
                start: null,
                end: null,
                roomId: null
            }),
            newComment: '',
        }
    },

    props: ['showHints', 'eventTypes', 'rooms', 'isAdmin', 'event', 'project', 'wantedRoomId', 'roomCollisions','showComments'],

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
        roomAdminIds() {
            let adminIds = [];
            this.selectedRoom?.room_admins?.forEach(admin => {
                adminIds.push(admin.id);
            })
            return adminIds;


        },
    },

    methods: {
        checkButtonDisabled(){
            if(this.series){
                if(this.seriesEndDate){
                    const eventEndDate = new Date(this.endFull);
                    const endDateSeries = new Date(this.seriesEndDate);
                    return endDateSeries < eventEndDate;
                }
                return true;
            }
            return false;
        },
        openModal() {
            this.canEdit = (!this.event?.id) || this.event?.canEdit || this.$page.props.can.create_and_edit_projects;
            if (!this.event) {
                if (this.project) {
                    this.selectedProject = {id: this.project.id, name: this.project.name};
                }
                return;
            }
            const start = dayjs(this.event.start);
            const end = dayjs(this.event.end);

            this.startDate = start?.format('YYYY-MM-DD');
            this.startTime = start?.format('HH:mm');
            this.endDate = end?.format('YYYY-MM-DD');
            this.endTime = end?.format('HH:mm');
            this.isLoud = this.event.isLoud
            this.audience = this.event.audience
            this.title = this.event.title
            this.eventName = this.event.eventName
            if (!this.event.eventTypeId) {
                this.selectedEventType = this.eventTypes[0];
            } else {
                this.selectedEventType = this.eventTypes.find(type => type.id === this.event.eventTypeId);
                if(this.selectedEventType.id === 1){
                    this.disableEventTypeSelector = true;
                }
            }
            this.series = this.event.is_series
            if(this.series){
                this.seriesEndDate = this.event.series.end_date;
            }
            this.frequencies.forEach((frequency) => {
                if(frequency.id === this.event.series?.frequency_id) {
                    this.selectedFrequency = frequency
                }
            })
            this.selectedProject = {id: this.event.projectId, name: this.event.projectName}
            if (this.wantedRoomId) {
                this.selectedRoom = this.rooms.find(room => room.id === this.wantedRoomId)
            } else if (this.event) {
                this.selectedRoom = this.rooms.find(type => type.id === this.event.roomId)
            }


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
            if(bool){
                this.$inertia.post(this.route('event.answer', {event: this.event.id}), {
                    comment: this.newComment,
                }, {preserveState: true, preserveScroll: true})
            }
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

        checkChanges() {
            this.updateTimes(this.event);

            if (this.event?.start && this.event?.end) {

                axios.post('/collision/room', {
                    params: {
                        start: this.event?.start,
                        end: this.event?.end,
                    }
                })
                    .then(response => this.roomCollisions = response.data);
            }


        },
        checkTypeChange() {

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
        updateTimes() {
            if (this.startDate) {
                if (!this.endDate) {
                    this.endDate = this.startDate;
                }
                if (this.startTime) {
                    if (!this.endTime) {
                        if (this.startTime === '23:00') {
                            this.endTime = '23:59';
                        } else {
                            let startHours = this.startTime.slice(0, 2);
                            if (startHours === '23') {
                                this.endTime = '00:' + this.startTime.slice(3, 5);
                                let date = new Date();
                                this.endDate = new Date(date.setDate(new Date(this.endDate).getDate() + 1)).toISOString().slice(0, 10);
                            } else {
                                this.endTime = this.getNextHourString(this.startTime)
                            }
                        }
                    }
                }
            }


            this.validateStartBeforeEndTime();

            this.checkCollisions();
            this.checkEventTimeLength()

        },
        async validateStartBeforeEndTime() {

            this.error = null;
            if (this.startDate && this.endDate && this.startTime && this.endTime) {
                this.setCombinedTimeString(this.startDate, this.startTime, 'start');
                this.setCombinedTimeString(this.endDate, this.endTime, 'end');
                return await axios
                    .post('/events', {start: this.startFull, end: this.endFull}, {headers: {'X-Dry-Run': true}})
                    .catch(error => this.error = error.response.data.errors);
            }

        },
        checkEventTimeLength() {
            // check if event min 30min
            if (this.startFull && this.endFull) {
                const date = new Date(this.startFull);
                const minimumEnd = this.addMinutes(date, 30);
                if (minimumEnd <= new Date(this.endFull)) {
                    this.helpTextLength = '';
                } else {
                    this.helpTextLength = 'Der Termin darf nicht kürzer als 30 Minuten sein';
                }
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
                    this.startFull = new Date(new Date(combinedDateString).setMinutes(new Date(combinedDateString).getMinutes() + 60)).toISOString().slice(0, 16);
                } else {
                    this.startFull = new Date(new Date(combinedDateString).setMinutes(new Date(combinedDateString).getMinutes() + 120)).toISOString().slice(0, 16);
                }
            } else if (target === 'end') {
                if (offset === -60) {
                    this.endFull = new Date(new Date(combinedDateString).setMinutes(new Date(combinedDateString).getMinutes() + 60)).toISOString().slice(0, 16);
                } else {
                    this.endFull = new Date(new Date(combinedDateString).setMinutes(new Date(combinedDateString).getMinutes() + 120)).toISOString().slice(0, 16);
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
        /**
         * Creates an event and reloads all events
         *
         * @returns {Promise<*>}
         */
        async updateAndAnswerEvent() {
            return await axios
                        .put('/events/' + this.event?.id, this.eventData())
                        .then(() => { this.closeModal(true);})
                        .catch(error => this.error = error.response.data.errors);
        },
        async singleSaveEvent(){
            return await axios
                .put('/events/' + this.event?.id, this.eventData())
                .then(() => { this.closeModal(true); this.closeSeriesEditModal() })
                .catch(error => this.error = error.response.data.errors);
        },
        async saveAllSeriesEvents(){
            this.allSeriesEvents = true;
            return await axios
                .put('/events/' + this.event?.id, this.eventData())
                .then(() => { this.closeModal(true); this.closeSeriesEditModal() })
                .catch(error => this.error = error.response.data.errors);
        },
        closeSeriesEditModal(){
            this.showSeriesEdit = false;
        },

        async afterConfirm(bool) {
            if (!bool) return this.deleteComponentVisible = false;

            return await axios
                .delete(`/events/${this.event.id}`)
                .then(() => this.closeModal(true));
        },
        async approveRequest(event) {
            this.answerRequestForm.accepted = true;
            this.answerRequestForm.put(route('events.accept', {event: event.id}));
            this.closeModal(true)
        },
        async declineRequest(event) {
            this.answerRequestForm.accepted = false;
            this.answerRequestForm.put(route('events.accept', {event: event}));
            this.closeModal(true)
        },
        chooseProject(project) {
            this.selectedProject = project;
            this.projectName = '';
        },
        toggleAccept(type) {
            if(type === 'option'){
                if (this.optionAccept) {
                    this.accept = false;
                    this.optionString = options[0].name;
                }
            }else{
                if(this.accept){
                    this.optionAccept = false;
                    this.optionString = null;
                }
            }
        },

        eventData() {
            return {
                title: this.title,
                eventName: this.eventName,
                start: this.formatDate(this.startDate, this.startTime),
                end: this.formatDate(this.endDate, this.endTime),
                roomId: this.selectedRoom?.id,
                description: this.description,
                audience: this.audience,
                isLoud: this.isLoud,
                isOption: this.isOption,
                eventNameMandatory: this.selectedEventType?.individual_name,
                projectId: this.selectedProject?.id,
                projectName: this.creatingProject ? this.projectName : '',
                eventTypeId: this.selectedEventType?.id,
                projectIdMandatory: this.selectedEventType?.project_mandatory && !this.creatingProject,
                creatingProject: this.creatingProject,
                declinedRoomId: this.declinedRoomId,
                is_series: this.series,
                seriesFrequency: this.selectedFrequency.id,
                seriesEndDate: this.seriesEndDate,
                allSeriesEvents: this.allSeriesEvents,
                adminComment: this.adminComment,
                optionString: this.optionAccept ? this.optionString : null,
                accept: this.accept,
                optionAccept: this.optionAccept,
                noNotifications: true
            };
        },
    },
}
</script>

<style scoped></style>
