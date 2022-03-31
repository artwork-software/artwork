<template>
    <app-layout title="Dashboard">
        <div class="py-12">
            <div class="max-w-screen-2xl my-12 flex flex-row mx-auto sm:px-6 lg:px-8">
                <div class="flex flex-1 flex-wrap">
                    <div class="w-full flex my-auto justify-between">
                        <div class="flex">
                            <h2 class="text-2xl flex">Alle Teams</h2>
                            <button @click="openAddTeamModal" type="button"
                                    class="flex my-auto ml-4 pr-2 items-center font-semibold p-1 border border-transparent rounded-full shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                <PlusSmIcon class="h-4 w-4 " aria-hidden="true"/>
                                Team hinzufügen
                            </button>
                        </div>
                        <div class="flex items-center">

                            <div class="inset-y-0 mr-3 pointer-events-none">
                                <SearchIcon class="h-5 w-5" aria-hidden="true"/>
                            </div>
                        </div>
                    </div>
                    <ul role="list" class="mt-4 divide-y divide-gray-200 w-full">
                        <li v-for="department in departments.data" :key="department.id"
                            class="py-4 flex justify-between">
                            <div class="flex">
                            <img class="h-14 w-14 rounded-full flex justify-start" :src="department.logo_url" alt=""/>
                            <div class="ml-3 my-auto w-full justify-start mr-6">
                                <div class="flex my-auto">
                                    <p class="text-md font-semibold text-gray-900">{{ department.name }}</p>
                                </div>
                            </div>
                            </div>
                            <div class="flex">
                            <div class="mt-3 mr-6" v-for="user in department.users">
                                <img class="h-8 w-8 rounded-full"
                                     :src="user.profile_photo_url"
                                     alt=""/>
                            </div>
                            <DotsVerticalIcon class="mr-3 flex-shrink-0 h-6 w-6 text-gray-600 my-auto"
                                              aria-hidden="true"/>
                            </div>
                        </li>
                    </ul>
                </div>


            </div>
        </div>
        <!-- Team erstellen Modal-->
        <jet-dialog-modal :show="addingTeam" @close="closeAddTeamModal">
            <template #content>
                <div class="mx-4">
                    <div class="font-bold text-2xl my-2">
                        Team erstellen
                    </div>
                    <XIcon @click="closeAddTeamModal" class="h-5 w-5 right-0 top-0 mt-8 mr-5 absolute cursor-pointer"
                           aria-hidden="true"/>
                    <div class="text-gray-500">
                        Lege Namen, Profilbild und die Mitglieder des Teams fest.
                    </div>
                    <div class="mt-4">
                        <div class="flex">
                    <img  class="h-14 w-14 rounded-full flex justify-start" :src="$page.props.user.profile_photo_url"
                          alt=""/>
                        <jet-input type="text" class="ml-4 mt-1 block w-3/4" placeholder="Name eingeben"
                                   v-model="this.form.name"
                        />
                        <jet-input-error :message="form.error" class="mt-2"/>
                        </div>
                        <div class="mt-4">
                            <div class="flex">
                            <!-- TODO: HIER INPUT MIT MEILISEARCH -->
                            <Listbox as="div" v-model="selected">
                                <ListboxLabel class="block text-sm font-medium text-gray-700"> Nutzer*in zum Hinzufügen
                                    auswählen
                                </ListboxLabel>
                                <div class="mt-1 relative">
                                    <ListboxButton
                                        class="relative w-full bg-white border border-gray-300 rounded-md shadow-sm pl-3 pr-10 py-2 text-left cursor-default focus:outline-none focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                                        <span class="flex items-center">
                                            <img :src="selected.profile_photo_url" alt=""
                                                 class="flex-shrink-0 h-6 w-6 rounded-full"/>
                                            <span class="ml-3 block truncate">{{ selected.first_name }} {{ selected.last_name }}</span>
                                        </span>
                                        <span
                                            class="ml-3 absolute inset-y-0 right-0 flex items-center pr-2 pointer-events-none">
                                            <SelectorIcon class="h-5 w-5 text-gray-400" aria-hidden="true"/>
                                        </span>
                                    </ListboxButton>

                                    <transition leave-active-class="transition ease-in duration-100"
                                                leave-from-class="opacity-100" leave-to-class="opacity-0">
                                        <ListboxOptions
                                            class="absolute z-10 mt-1 w-full bg-white shadow-lg max-h-56 rounded-md py-1 text-base ring-1 ring-black ring-opacity-5 overflow-auto focus:outline-none sm:text-sm">
                                            <ListboxOption as="template" v-for="user in users.data" :key="user.email"
                                                           :value="user" v-slot="{ active, selected }">
                                                <li :class="[active ? 'text-white bg-indigo-600' : 'text-gray-900', 'cursor-default select-none relative py-2 pl-3 pr-9']">
                                                    <div class="flex items-center">
                                                        <img :src="user.profile_photo_url" alt=""
                                                             class="flex-shrink-0 h-6 w-6 rounded-full"/>
                                                        <span
                                                            :class="[selected ? 'font-semibold' : 'font-normal', 'ml-3 block truncate']">
                                                            {{ user.first_name }} {{ user.last_name }}
                                                        </span>
                                                    </div>

                                                    <span v-if="selected"
                                                          :class="[active ? 'text-white' : 'text-indigo-600', 'absolute inset-y-0 right-0 flex items-center pr-4']">
                                                        <CheckIcon class="h-5 w-5" aria-hidden="true"/>
                                                     </span>
                                                </li>
                                            </ListboxOption>
                                        </ListboxOptions>
                                    </transition>
                                </div>
                            </Listbox>
                                <div class="flex items-center">
                            <Button @click="addUserToAssignedUsersArray"
                                    class="mt-6 ml-2 inline-flex items-center px-6 border border-transparent text-sm shadow-sm text-white bg-indigo-900 hover:bg-indigo-700 focus:outline-none">
                                Hinzufügen
                            </Button>
                                </div>
                            </div>


                            <h4 class="mt-2 mb-1" v-show="this.form.assigned_users.length >= 1">Aktuelle Teammitglieder:</h4>
                            <span  class="flex" v-for="(user,index) in form.assigned_users">
                                <img  class="h-14 w-14 rounded-full flex justify-start" :src="user.profile_photo_url"
                                     alt=""/>
                                <button type="button" @click="deleteUserFromAssignedUsersArray(index)"
                                        class="flex-shrink-0 h-4 w-4 rounded-full inline-flex items-center justify-center text-indigo-400 hover:bg-indigo-200 hover:text-indigo-500 focus:outline-none focus:bg-indigo-500 focus:text-white">
                                    <span class="sr-only">Nutzer*in aus Team entfernen</span>
                                    <svg class="h-2 w-2" stroke="currentColor" fill="none" viewBox="0 0 8 8">
                                        <path stroke-linecap="round" stroke-width="1.5" d="M1 1l6 6m0-6L1 7"/>
                                    </svg>
                                </button>
                             </span>
                        </div>
                        <Button :class="[this.form.assigned_users.length === 0 || this.form.name === '' ? 'bg-gray-400': 'bg-indigo-900 hover:bg-indigo-700 focus:outline-none']" class="mt-4 inline-flex items-center px-20 py-3 border border-transparent text-base font-bold uppercase shadow-sm text-white " @click="addTeam"
                                :disabled="this.form.assigned_users.length === 0  || this.form.name === ''">
                            Team erstellen
                        </Button>
                    </div>

                </div>

            </template>
        </jet-dialog-modal>
    </app-layout>
</template>

<script>

import {defineComponent} from 'vue'
import AppLayout from '@/Layouts/AppLayout.vue'
import {DotsVerticalIcon, InformationCircleIcon, XIcon} from '@heroicons/vue/outline'
import {ChevronDownIcon, ChevronUpIcon, PlusSmIcon} from '@heroicons/vue/solid'
import {SearchIcon} from "@heroicons/vue/outline";
import {ref} from 'vue'
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
import {CheckIcon, SelectorIcon} from '@heroicons/vue/solid'
import Button from "@/Jetstream/Button";
import JetButton from "@/Jetstream/Button";
import JetDialogModal from "@/Jetstream/DialogModal";
import JetInput from "@/Jetstream/Input";
import JetInputError from "@/Jetstream/InputError";
import JetSecondaryButton from "@/Jetstream/SecondaryButton";
import Checkbox from "@/Layouts/Components/Checkbox";
import {useForm} from "@inertiajs/inertia-vue3";


export default defineComponent({
    components: {
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
        SelectorIcon,
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
        XIcon
    },
    props: ['departments', 'users'],
    methods: {
        openAddTeamModal() {
            this.addingTeam = true;
            this.showX = [];
        },
        addUserToAssignedUsersArray() {
            if(!this.form.assigned_users.includes(this.selected)){
                this.form.assigned_users.push(this.selected);
            }
        },
        deleteUserFromAssignedUsersArray(index) {
            this.form.assigned_users.splice(index, 1);
        },
        addTeam() {
            console.log("USERS: " + this.form.assigned_users);
            console.log("NAME: " + this.form.name);
            this.form.post(route('departments.store'), {

            })

        },
        closeAddTeamModal() {
            this.addingTeam = false;
            this.showX = [];
            this.form.assigned_users = [];
            this.form.name = "";
            this.form.logo = "";

        },
    },
    data() {
        return {
            addingTeam: false,
            showX: [],
            form: useForm({
                logo:"",
                name:"",
                assigned_users: [],

            }),
        }
    },
    setup(props) {
        const selected = ref(props.users[0])

        return {
            selected,
        }
    }
})
</script>
