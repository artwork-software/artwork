<template>
    <app-layout>
        <div class="flex">
            <!-- Greetings Div -->
            <div class="mr-2 w-4/6">
                <div class="ml-12 mt-10">
                    <h2 class="headline1 flex mb-4">Benachrichtungen</h2>
                </div>
            </div>
        </div>
        <div class="ml-12 mt-8">
            <div class="mb-4 border-gray-200 dark:border-gray-700">
                <ul class="flex flex-wrap -mb-px text-sm font-medium text-center">
                    <li class="mr-2" role="presentation">
                        <button
                            :class="[openTab === 'notifications' ? 'border-buttonBlue text-buttonBlue' : 'border-transparent text-secondary hover:text-gray-600 hover:border-gray-300', 'py-4 px-2 border-b-2 font-semibold']"
                            @click="openTab = 'notifications'">BENACHRICHTIGUNGEN
                        </button>
                    </li>
                    <li class="mr-2" role="presentation">
                        <button
                            :class="[openTab === 'mailSettings' ? 'border-buttonBlue text-buttonBlue' : 'border-transparent text-secondary hover:text-gray-600 hover:border-gray-300', 'py-4 px-2 border-b-2 font-semibold']"
                            @click="openTab = 'mailSettings'">E-MAIL-EINSTELLUNGEN
                        </button>
                    </li>
                    <li class="mr-2" role="presentation">
                        <button
                            :class="[openTab === 'pushSettings' ? 'border-buttonBlue text-buttonBlue' : 'border-transparent text-secondary hover:text-gray-600 hover:border-gray-300', 'py-4 px-2 border-b-2 font-semibold']"
                            @click="openTab = 'pushSettings'">PUSH-EINSTELLUNGEN
                        </button>
                    </li>
                </ul>
            </div>
            <div class="">
                <div class="flex" v-if="openTab === 'notifications'">
                    <button class="bg-buttonBlue flex relative w-6"
                            @click="showRoomsAndEvents = !showRoomsAndEvents">
                        <ChevronUpIcon v-if="showRoomsAndEvents"
                                       class="h-6 w-6 text-white my-auto"></ChevronUpIcon>
                        <ChevronDownIcon v-else
                                         class="h-6 w-6 text-white my-auto"></ChevronDownIcon>
                    </button>
                    <div class="flex flex-wrap w-11/12 border border-2 border-gray-300">
                        <div class="flex justify-between w-full mb-6 ml-12">
                            <div class="flex headline2 my-10">
                                Raumbelegungen & Termine
                            </div>
                            <div class="flex justify-end xsLight mr-8 my-10">
                                alle archivieren
                            </div>
                        </div>
                        <div class="flex border-t-2 border-b-2 mx-12 py-8" v-if="showRoomsAndEvents"
                             v-for="notification in notifications">
                            <div class="flex">
                                <EventTypeIconCollection :height="32" :width="32"
                                                         iconName="eventType0"/>
                                <div class="flex">
                                    <div class="sDark">
                                        {{ notification[0].data.title }}
                                    </div>
                                    <div class="xsLight">
                                        <!-- HIER FEHLT NOCH CREATED_AT? und dann Design umsetzen -->
                                        {{ notification[0].data.event }}
                                    </div>
                                </div>
                                <div>

                                </div>
                            </div>
                            <div>
                                {{ notification[0].data }}
                            </div>
                        </div>
                    </div>
                </div>
                <div v-if="openTab === 'mailSettings'">
                    <p class="text-sm text-gray-500 dark:text-gray-400">

                    </p>
                </div>
                <div v-if="openTab === 'pushSettings'">
                    <p class="text-sm text-gray-500 dark:text-gray-400">

                    </p>
                </div>
            </div>
        </div>

    </app-layout>
</template>

<script>

import {defineComponent} from 'vue'
import AddButton from "@/Layouts/Components/AddButton";
import AppLayout from '@/Layouts/AppLayout.vue'
import {
    DotsVerticalIcon,
    ChevronDownIcon,
    InformationCircleIcon,
    XIcon,
    PencilAltIcon,
    TrashIcon
} from '@heroicons/vue/outline'
import {ChevronUpIcon, PlusSmIcon, CheckIcon, XCircleIcon} from '@heroicons/vue/solid'
import {SearchIcon} from "@heroicons/vue/outline";

import {
    Listbox,
    ListboxButton,
    ListboxLabel,
    ListboxOption,
    ListboxOptions,
    Menu,
    MenuButton,
    MenuItem, MenuItems
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
import {forEach} from "lodash";
import {Inertia} from "@inertiajs/inertia";
import UserTooltip from "@/Layouts/Components/UserTooltip";
import TeamIconCollection from "@/Layouts/Components/TeamIconCollection";
import InputComponent from "@/Layouts/Components/InputComponent";
import EventTypeIconCollection from "@/Layouts/Components/EventTypeIconCollection";


export default defineComponent({
    components: {
        AddButton,
        TeamIconCollection,
        UserTooltip,
        SvgCollection,
        Button,
        AppLayout,
        DotsVerticalIcon,
        PlusSmIcon,
        SearchIcon,
        Listbox,
        ListboxButton,
        ListboxLabel,
        ListboxOption,
        ListboxOptions,
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
        InformationCircleIcon,
        ChevronDownIcon,
        ChevronUpIcon,
        Checkbox,
        XIcon,
        PencilAltIcon,
        TrashIcon,
        XCircleIcon,
        Link,
        InputComponent,
        EventTypeIconCollection
    },
    props: ['notifications'],
    created() {

    },
    methods: {},
    watch: {},
    data() {
        return {
            openTab: 'notifications',
            showRoomsAndEvents: true,
        }
    },
    setup() {
        return {}
    }
})
</script>
