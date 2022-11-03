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
        <div class="ml-12 mt-10">
            <div class="mb-4 border-b border-gray-200 dark:border-gray-700">
                <ul class="flex flex-wrap -mb-px text-sm font-medium text-center" id="myTab" data-tabs-toggle="#myTabContent" role="tablist">
                    <li class="mr-2" role="presentation">
                        <button class="inline-block p-4 rounded-t-lg border-b-2" id="profile-tab" data-tabs-target="#profile" type="button" role="tab" aria-controls="profile" aria-selected="false">BENACHRICHTIGUNGEN</button>
                    </li>
                    <li class="mr-2" role="presentation">
                        <button class="inline-block p-4 rounded-t-lg border-b-2 border-transparent hover:text-gray-600 hover:border-gray-300 dark:hover:text-gray-300" id="dashboard-tab" data-tabs-target="#dashboard" type="button" role="tab" aria-controls="dashboard" aria-selected="false">E-MAIL-EINSTELLUNGEN</button>
                    </li>
                    <li class="mr-2" role="presentation">
                        <button class="inline-block p-4 rounded-t-lg border-b-2 border-transparent hover:text-gray-600 hover:border-gray-300 dark:hover:text-gray-300" id="settings-tab" data-tabs-target="#settings" type="button" role="tab" aria-controls="settings" aria-selected="false">PUSH-EINSTELLUNGEN</button>
                    </li>
                </ul>
            </div>
            <div id="myTabContent">
                <div class="hidden p-4" id="profile" role="tabpanel" aria-labelledby="profile-tab">
                    <div v-for="notification in notifications">
                        <pre>
                            {{ notification }}
                        </pre>
                    </div>
                </div>
                <div class="hidden p-4" id="dashboard" role="tabpanel" aria-labelledby="dashboard-tab">
                    <p class="text-sm text-gray-500 dark:text-gray-400">

                    </p>
                </div>
                <div class="hidden p-4" id="settings" role="tabpanel" aria-labelledby="settings-tab">
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
        InputComponent
    },
    props: ['notifications'],
    created() {

    },
    methods: {

    },
    watch: {


    },
    data() {
        return {

        }
    },
    setup() {
        return {

        }
    }
})
</script>
