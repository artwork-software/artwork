<template>
    <app-layout title="Event Requests">
        <div class="py-4">
            <div class="max-w-screen-lg mb-40 my-12 flex flex-row ml-20 mr-40">
                <div class="flex flex-1 flex-wrap">
                    <div class="w-full flex my-auto justify-between">
                        <div class="flex flex-wrap">
                            <h2 class="text-3xl font-black font-lexend flex w-full">Belegungsanfragen</h2>
                            <div class="text-secondary subpixel-antialiased flex mt-4">
                                Hier siehst du alle Raumbelegungsanfragen auf einen Blick und kannst sie verwalten.
                            </div>
                        </div>
                    </div>
                    <div class="flex flex-wrap">
                        <div v-for="eventRequest in event_requests.data" class="flex flex-wrap w-full items-center">
                            <div class="flex w-full items-center flex-wrap">

                                <div class="flex items-center w-full mt-4">
                                    <div class=" w-1/4 text-lg flex leading-6 font-bold font-lexend text-gray-900">
                                        {{ eventRequest.room.name }}:
                                    </div>
                                    <div class="flex items-center w-full">
                                        <EventTypeIconCollection :height="26" :width="26"
                                                                 :iconName="eventRequest.event_type.svg_name"/>
                                        <div
                                            class="whitespace-nowrap ml-2 text-lg flex leading-6 font-bold font-lexend text-gray-900">
                                            {{ eventRequest.event_type.name }}
                                            <AdjustmentsIcon v-if="eventRequest.occupancy_option"
                                                             class="h-5 w-5 ml-2 my-auto"/>
                                            <img src="/Svgs/IconSvgs/icon_public.svg" v-if="eventRequest.audience" class="h-5 w-5 ml-2 my-auto"/>
                                            <img src="/Svgs/IconSvgs/icon_loud.svg" v-if="eventRequest.is_loud" class="h-5 w-5 ml-2 my-auto"/>
                                        </div>

                                        <div class="flex w-full whitespace-nowrap ml-3"
                                             v-if="eventRequest.start_time.split(',')[0] === eventRequest.end_time.split(',')[0]">
                                            {{ getGermanWeekdayAbbreviation(eventRequest.start_time_weekday) }}, {{
                                                eventRequest.start_time.split(',')[0]
                                            }},{{ eventRequest.start_time.split(',')[1] }}
                                            - {{ eventRequest.end_time.split(',')[1] }}
                                        </div>
                                        <div class="flex w-full whitespace-nowrap ml-3" v-else>
                                            {{ getGermanWeekdayAbbreviation(eventRequest.start_time_weekday) }},
                                            {{ eventRequest.start_time }} -
                                            {{ getGermanWeekdayAbbreviation(eventRequest.end_time_weekday) }},
                                            {{ eventRequest.end_time }}
                                        </div>
                                        <button @click="openApproveRequestModal(eventRequest)" type="button"
                                                class="flex my-auto ml-6 p-0.5 items-center border border-transparent rounded-full shadow-sm text-white bg-primary hover:bg-primaryHover focus:outline-none hover:bg-success">
                                            <CheckIcon class="h-4 w-4 flex flex-shrink" aria-hidden="true"/>
                                        </button>
                                        <button @click="openDeclineRequestModal(eventRequest)" type="button"
                                                class="flex my-auto ml-6 p-0.5 items-center border border-transparent rounded-full shadow-sm text-white bg-primary hover:bg-primaryHover focus:outline-none hover:bg-error">
                                            <XIcon class="h-4 w-4 flex flex-shrink" aria-hidden="true"/>
                                        </button>
                                    </div>
                                </div>
                                <div class="flex items-center w-full ml-44 justify-between">
                                    <div v-if="eventRequest.project" class="w-80">
                                        <div class="ml-16 text-secondary text-sm flex items-center">
                                            Zugeordnet zu
                                            <Link :href="route('projects.show',{project: eventRequest.project.id, month_start: new Date((new Date).getFullYear(),(new Date).getMonth(),1,0,120),month_end:new Date((new Date).getFullYear(),(new Date).getMonth() + 1,2), calendarType: 'monthly'})"
                                                  class="text-secondary font-black leading-3 subpixel-antialiased ml-2">
                                                {{ eventRequest.project.name }}
                                            </Link>
                                        </div>

                                        <div v-for="projectLeader in eventRequest.project.project_managers">
                                            <img :data-tooltip-target="projectLeader.id"
                                                 :src="projectLeader.profile_photo_url"
                                                 :alt="projectLeader.name"
                                                 class="ml-2 ring-white ring-2 rounded-full h-7 w-7 object-cover"/>
                                            <UserTooltip :user="projectLeader"/>
                                        </div>

                                    </div>
                                    <div class="text-secondary text-sm w-64 ml-16" v-else>
                                        Keinem Projekt zugeordnet
                                    </div>
                                    <div class="flex text-sm text-secondary items-center">
                                        angefragt:<img :data-tooltip-target="eventRequest.created_by.id"
                                                       :src="eventRequest.created_by.profile_photo_url"
                                                       :alt="eventRequest.created_by.name"
                                                       class="ml-2 ring-white ring-2 rounded-full h-7 w-7 object-cover"/>
                                        <UserTooltip :user="eventRequest.created_by"/>
                                        <span class="ml-2"> {{ eventRequest.created_at }}</span>
                                    </div>

                                    <div>

                                    </div>
                                </div>

                                <div class="flex ml-40 mt-2 text-sm text-secondary items-center w-full"
                                     v-if="eventRequest.description">
                                    {{ eventRequest.description }}
                                </div>


                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- Approve Request Modal -->
        <jet-dialog-modal :show="showApproveRequestModal" @close="closeApproveRequestModal">
            <template #content>
                <img src="/Svgs/Overlays/illu_success.svg" class="-ml-6 -mt-8 mb-4" />
                <div class="mx-4">
                    <div class="font-bold text-primary font-lexend text-2xl my-2">
                        Raumbelegung zusagen
                    </div>
                    <XIcon @click="closeApproveRequestModal"
                           class="h-5 w-5 right-0 top-0 mr-5 mt-8 flex text-secondary absolute cursor-pointer"
                           aria-hidden="true"/>
                    <div class="text-success">
                        Bist du sicher, dass du die Raumbelegung zusagen möchtest?
                    </div>
                    <div class="flex flex-wrap w-full items-center">
                        <div class="flex w-full items-center flex-wrap">

                            <div class="flex items-center w-full mt-4">
                                <div class="flex items-center ml-12 w-full">
                                    <EventTypeIconCollection :height="26" :width="26"
                                                             :iconName="requestToApprove.event_type.svg_name"/>
                                    <div
                                        class="whitespace-nowrap ml-2 text-lg flex leading-6 font-bold font-lexend text-gray-900">
                                        {{ requestToApprove.event_type.name }}
                                        <AdjustmentsIcon v-if="requestToApprove.occupancy_option"
                                                         class="h-5 w-5 ml-2 my-auto"/>
                                        <img src="/Svgs/IconSvgs/icon_public.svg" v-if="requestToApprove.audience" class="h-5 w-5 ml-2 my-auto"/>
                                        <img src="/Svgs/IconSvgs/icon_loud.svg" v-if="requestToApprove.is_loud" class="h-5 w-5 ml-2 my-auto"/>
                                    </div>

                                    <div class="flex w-full whitespace-nowrap ml-3"
                                         v-if="requestToApprove.start_time.split(',')[0] === requestToApprove.end_time.split(',')[0]">
                                        {{ getGermanWeekdayAbbreviation(requestToApprove.start_time_weekday) }}, {{
                                            requestToApprove.start_time.split(',')[0]
                                        }},{{ requestToApprove.start_time.split(',')[1] }}
                                        - {{ requestToApprove.end_time.split(',')[1] }}
                                    </div>
                                    <div class="flex w-full whitespace-nowrap ml-3" v-else>
                                        {{ getGermanWeekdayAbbreviation(requestToApprove.start_time_weekday) }},
                                        {{ requestToApprove.start_time }} -
                                        {{ getGermanWeekdayAbbreviation(requestToApprove.end_time_weekday) }},
                                        {{ requestToApprove.end_time }}
                                    </div>
                                </div>
                            </div>
                            <div class="flex items-center w-full ml-2 justify-between">
                                <div v-if="requestToApprove.project" class="w-80">
                                    <div class="ml-16 text-secondary text-sm flex items-center">
                                        Zugeordnet zu
                                        <div class="text-secondary font-black leading-3 subpixel-antialiased ml-2">
                                            {{ requestToApprove.project.name }}
                                        </div>
                                    </div>
                                    <!--
                                    <div v-for="projectLeader in requestToApprove.project.project_managers">
                                        <img :data-tooltip-target="projectLeader.id"
                                             :src="projectLeader.profile_photo_url"
                                             :alt="projectLeader.name"
                                             class="ml-2 ring-white ring-2 rounded-full h-7 w-7 object-cover"/>
                                        <UserTooltip :user="projectLeader"/>
                                    </div>
                                    -->
                                </div>
                                <div class="text-secondary text-sm ml-10" v-else>
                                    Keinem Projekt zugeordnet
                                </div>
                                <div class="flex text-sm text-secondary items-center">
                                    angefragt:<img :data-tooltip-target="requestToApprove.created_by.id"
                                                   :src="requestToApprove.created_by.profile_photo_url"
                                                   :alt="requestToApprove.created_by.name"
                                                   class="ml-2 ring-white ring-2 rounded-full h-7 w-7 object-cover"/>
                                    <UserTooltip :user="requestToApprove.created_by"/>
                                    <span class="ml-2"> {{ requestToApprove.created_at }}</span>
                                </div>
                                <div>

                                </div>
                            </div>
                            <div class="flex ml-12 mt-2 text-sm text-secondary items-center w-full"
                                 v-if="requestToApprove.description">
                                {{ requestToApprove.description }}
                            </div>
                        </div>
                    </div>
                        <div class="flex justify-between mt-6">
                            <button class="bg-primary focus:outline-none my-auto inline-flex items-center px-20 py-3 border border-transparent
                            text-base font-bold uppercase shadow-sm text-secondaryHover"
                                    @click="approveRequest">
                                Zusagen
                            </button>
                            <div class="flex my-auto">
                            <span @click="closeApproveRequestModal"
                                  class="text-secondary subpixel-antialiased cursor-pointer">Nein, doch nicht</span>
                            </div>
                        </div>
                    </div>
            </template>
        </jet-dialog-modal>
        <!-- Decline Request Modal -->
        <jet-dialog-modal :show="showDeclineRequestModal" @close="closeDeclineRequestModal">
            <template #content>
                <img src="/Svgs/Overlays/illu_warning.svg" class="-ml-6 -mt-8 mb-4" />
                <div class="mx-4">
                    <div class="font-black font-lexend text-primary text-3xl my-2">
                        Raumbelegung absagen
                    </div>
                    <XIcon @click="closeDeclineRequestModal"
                           class="h-5 w-5 right-0 top-0 mr-5 mt-8 flex text-secondary absolute cursor-pointer"
                           aria-hidden="true"/>
                    <div class="text-error subpixel-antialiased">
                        Bist du sicher, dass du die Raumbelegung absagen möchtest?
                    </div>
                    <div class="flex flex-wrap w-full items-center">
                        <div class="flex w-full items-center flex-wrap">

                            <div class="flex items-center w-full mt-4">
                                <div class="flex items-center ml-12 w-full">
                                    <EventTypeIconCollection :height="26" :width="26"
                                                             :iconName="requestToDecline.event_type.svg_name"/>
                                    <div
                                        class="whitespace-nowrap ml-2 text-lg flex leading-6 font-bold font-lexend text-gray-900">
                                        {{ requestToDecline.event_type.name }}
                                        <AdjustmentsIcon v-if="requestToDecline.occupancy_option"
                                                         class="h-5 w-5 ml-2 my-auto"/>
                                        <img src="/Svgs/IconSvgs/icon_public.svg" v-if="requestToDecline.audience" class="h-5 w-5 ml-2 my-auto"/>
                                        <img src="/Svgs/IconSvgs/icon_loud.svg" v-if="requestToDecline.is_loud" class="h-5 w-5 ml-2 my-auto"/>
                                    </div>

                                    <div class="flex w-full whitespace-nowrap ml-3"
                                         v-if="requestToDecline.start_time.split(',')[0] === requestToDecline.end_time.split(',')[0]">
                                        {{ getGermanWeekdayAbbreviation(requestToDecline.start_time_weekday) }}, {{
                                            requestToDecline.start_time.split(',')[0]
                                        }},{{ requestToDecline.start_time.split(',')[1] }}
                                        - {{ requestToDecline.end_time.split(',')[1] }}
                                    </div>
                                    <div class="flex w-full whitespace-nowrap ml-3" v-else>
                                        {{ getGermanWeekdayAbbreviation(requestToDecline.start_time_weekday) }},
                                        {{ requestToDecline.start_time }} -
                                        {{ getGermanWeekdayAbbreviation(requestToDecline.end_time_weekday) }},
                                        {{ requestToDecline.end_time }}
                                    </div>
                                </div>
                            </div>
                            <div class="flex items-center w-full ml-2 justify-between">
                                <div v-if="requestToDecline.project" class="w-80">
                                    <div class="ml-16 text-secondary text-sm flex items-center">
                                        Zugeordnet zu
                                        <div class="text-secondary font-black leading-3 subpixel-antialiased ml-2">
                                            {{ requestToDecline.project.name }}
                                        </div>
                                    </div>
                                    <!--
                                    <div v-for="projectLeader in requestToApprove.project.project_managers">
                                        <img :data-tooltip-target="projectLeader.id"
                                             :src="projectLeader.profile_photo_url"
                                             :alt="projectLeader.name"
                                             class="ml-2 ring-white ring-2 rounded-full h-7 w-7 object-cover"/>
                                        <UserTooltip :user="projectLeader"/>
                                    </div>
                                    -->
                                </div>
                                <div class="text-secondary text-sm ml-10" v-else>
                                    Keinem Projekt zugeordnet
                                </div>
                                <div class="flex text-sm text-secondary items-center">
                                    angefragt:<img :data-tooltip-target="requestToDecline.created_by.id"
                                                   :src="requestToDecline.created_by.profile_photo_url"
                                                   :alt="requestToDecline.created_by.name"
                                                   class="ml-2 ring-white ring-2 rounded-full h-7 w-7 object-cover"/>
                                    <UserTooltip :user="requestToDecline.created_by"/>
                                    <span class="ml-2"> {{ requestToDecline.created_at }}</span>
                                </div>
                                <div>

                                </div>
                            </div>
                            <div class="flex ml-12 mt-2 text-sm text-secondary items-center w-full"
                                 v-if="requestToDecline.description">
                                {{ requestToDecline.description }}
                            </div>
                        </div>
                    </div>
                    <div class="flex justify-between mt-6">
                        <button class="bg-primary focus:outline-none my-auto inline-flex items-center px-20 py-3 border border-transparent
                            text-base font-bold uppercase shadow-sm text-secondaryHover"
                                @click="declineRequest">
                            Absagen
                        </button>
                        <div class="flex my-auto">
                            <span @click="closeDeclineRequestModal"
                                  class="text-secondary subpixel-antialiased cursor-pointer">Nein, doch nicht</span>
                        </div>
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
    AdjustmentsIcon,
} from '@heroicons/vue/outline'
import {ChevronUpIcon, PlusSmIcon, CheckIcon, XCircleIcon, XIcon} from '@heroicons/vue/solid'

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
import UserTooltip from "@/Layouts/Components/UserTooltip";


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
        Switch,
        UserTooltip
    },
    props: ['event_requests'],
    methods: {
        getGermanWeekdayAbbreviation(englishWeekday) {
            switch (englishWeekday) {
                case 'Monday':
                    return 'Mo';
                case 'Tuesday':
                    return 'Di';
                case 'Wednesday':
                    return 'Mi';
                case 'Thursday':
                    return 'Do';
                case 'Friday':
                    return 'Fr';
                case 'Saturday':
                    return 'Sa';
                case 'Sunday':
                    return 'So';
            }
        },
        openApproveRequestModal(eventRequest) {
            this.requestToApprove = eventRequest;
            this.showApproveRequestModal = true;
        },
        closeApproveRequestModal() {
            this.showApproveRequestModal = false;
            this.requestToApprove = null;
        },
        openDeclineRequestModal(eventRequest) {
            this.requestToDecline = eventRequest;
            this.showDeclineRequestModal = true;
        },
        closeDeclineRequestModal() {
            this.showDeclineRequestModal = false;
            this.requestToDecline = null;
        },
        approveRequest() {
            this.approveRequestForm.name = this.requestToApprove.name;
            this.approveRequestForm.start_time = this.requestToApprove.start_time_dt_local;
            this.approveRequestForm.end_time = this.requestToApprove.end_time_dt_local;
            this.approveRequestForm.description = this.requestToApprove.description;
            this.approveRequestForm.occupancy_option = false;
            this.approveRequestForm.is_loud = this.requestToApprove.is_loud;
            this.approveRequestForm.audience = this.requestToApprove.audience;
            if(this.requestToApprove.room){
                this.approveRequestForm.room_id = this.requestToApprove.room.id;
            }
            if(this.requestToApprove.project){
                this.approveRequestForm.project_id = this.requestToApprove.project.id;
            }
            this.approveRequestForm.event_type_id = this.requestToApprove.event_type.id;
            this.approveRequestForm.patch(route('events.update', {event: this.requestToApprove.id}));
            this.closeApproveRequestModal();
        },
        declineRequest() {
            this.approveRequestForm.name = this.requestToDecline.name;
            this.approveRequestForm.start_time = this.requestToDecline.start_time_dt_local;
            this.approveRequestForm.end_time = this.requestToDecline.end_time_dt_local;
            this.approveRequestForm.description = this.requestToDecline.description;
            this.approveRequestForm.occupancy_option = false;
            this.approveRequestForm.is_loud = this.requestToDecline.is_loud;
            this.approveRequestForm.audience = this.requestToDecline.audience;
            this.approveRequestForm.room_id = null;
            if(this.requestToDecline.project){
                this.approveRequestForm.project_id = this.requestToDecline.project.id;
            }
            this.approveRequestForm.event_type_id = this.requestToDecline.event_type.id;
            this.approveRequestForm.patch(route('events.update', {event: this.requestToDecline.id}));
            this.closeDeclineRequestModal();
        }
    },
    data() {
        return {
            showApproveRequestModal: false,
            requestToApprove: null,
            showDeclineRequestModal: false,
            requestToDecline: null,
            approveRequestForm: useForm({
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
            declineRequestForm: useForm({
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
        }
    },
})
</script>
