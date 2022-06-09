<template>
    <app-layout title="Event Requests">
        <div class="py-4">
            <div class="max-w-screen-lg mb-40 my-12 flex flex-row ml-20 mr-40">
                <div class="flex flex-1 flex-wrap">
                    <div class="w-full flex my-auto justify-between">
                        <div class="flex flex-wrap">
                            <h2 class="text-2xl flex w-full">Belegungsanfragen</h2>
                            <div class="text-secondary subpixel-antialiased flex mt-4">
                                Hier siehst du alle Raumbelegungsanfragen auf einen Blick und kannst sie verwalten.
                            </div>
                        </div>
                    </div>
                    <div class="flex flex-wrap">
                        <div v-for="eventRequest in event_requests.data" class="flex flex-wrap w-full items-center">
                            <div class="flex w-full">
                                <div class="text-lg w-full flex leading-6 font-bold font-lexend text-gray-900">
                                    {{ eventRequest.room.name }}:
                                </div>
                                <div class="flex ml-12 items-center">
                                    <EventTypeIconCollection :height="20" :width="20"
                                                             :iconName="eventRequest.event_type.svg_name"/>
                                    <span class="ml-2 text-lg flex leading-6 font-bold font-lexend text-gray-900">
                                    {{ eventRequest.event_type.name }}
                                </span>
                                    <AdjustmentsIcon v-if="eventRequest.occupancy_option" class="h-5 w-5 ml-2 my-auto"/>
                                    <UserGroupIcon v-if="eventRequest.audience" class="h-5 w-5 ml-2 my-auto"/>
                                    <VolumeUpIcon v-if="eventRequest.is_loud" class="h-5 w-5 ml-2 my-auto"/>
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
                                    <button @click="approveRequest" type="button"
                                            class="flex my-auto ml-6 p-0.5 items-center border border-transparent rounded-full shadow-sm text-white bg-primary hover:bg-primaryHover focus:outline-none">
                                        <CheckIcon class="h-4 w-4 flex flex-shrink" aria-hidden="true"/>
                                    </button>
                                    <button @click="approveOption" type="button"
                                            class="flex my-auto ml-6 p-0.5 items-center border border-transparent rounded-full shadow-sm text-white bg-primary hover:bg-primaryHover focus:outline-none">
                                        <AdjustmentsIcon class="h-4 w-4 flex flex-shrink" aria-hidden="true"/>
                                    </button>
                                    <button @click="declineRequest" type="button"
                                            class="flex my-auto ml-6 p-0.5 items-center border border-transparent rounded-full shadow-sm text-white bg-primary hover:bg-primaryHover focus:outline-none">
                                        <XIcon class="h-4 w-4 flex flex-shrink" aria-hidden="true"/>
                                    </button>
                                </div>
                                <div class="flex">
                                    <div v-if="eventRequest.project">
                                        <div class="flex">
                                            Zugeordnet zu
                                        </div>
                                        {{eventRequest.project}}
                                        <div v-for="projectLeader in eventRequest.project.project_managers">
                                            <img :data-tooltip-target="projectLeader.id"
                                                 :src="projectLeader.profile_photo_url"
                                                 :alt="projectLeader.name"
                                                 class="ml-2 ring-white ring-2 rounded-full h-7 w-7 object-cover"/>
                                            <UserTooltip :user="projectLeader"/></div>
                                    </div>
                                    <div>
                                        angefragt: <span> {{eventRequest.created_at}}</span>
                                    </div>
                                    <div>

                                    </div>
                                </div>


                            </div>
                        </div>
                    </div>
                    {{ event_requests.data }}

                </div>


            </div>
        </div>
    </app-layout>
</template>

<script>

import {defineComponent} from 'vue'
import AppLayout from '@/Layouts/AppLayout.vue'
import {
    DotsVerticalIcon,
    ChevronDownIcon,
    AdjustmentsIcon,
    UserGroupIcon,
    VolumeUpIcon
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
        UserGroupIcon,
        VolumeUpIcon,
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
        }
    },
    data() {
        return {}
    },
})
</script>
