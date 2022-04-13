<template>
    <app-layout title="Dashboard">
        <div class="py-4">
            <div class="max-w-screen-lg mb-40 my-12 flex flex-row ml-20 mr-40">
                <div class="flex flex-1 flex-wrap">
                    <div class="w-full flex my-auto justify-between">
                        <div class="flex">
                            <h2 class="text-2xl flex">Alle Teams</h2>
                            <button @click="openAddTeamModal" type="button"
                                    class="flex my-auto ml-6 items-center border border-transparent rounded-full shadow-sm text-white bg-primary hover:bg-primaryHover focus:outline-none">
                                <PlusSmIcon class="h-5 w-5" aria-hidden="true"/>
                            </button>
                            <div v-if="$page.props.can.show_hints" class="flex mt-1">
                                <SvgCollection svgName="arrowLeft" class="mt-1 ml-2"/>
                                <span class="font-nanum text-secondary tracking-tight ml-1 my-auto tracking-tight text-lg">Stelle neue Teams zusammen</span>
                            </div>
                        </div>
                        <div class="flex items-center">

                            <div class="inset-y-0 mr-3 pointer-events-none">
                                <SearchIcon class="h-5 w-5" aria-hidden="true"/>
                            </div>
                        </div>
                    </div>
                    <ul role="list" class="mt-5 w-full">
                        <li v-for="(department,index) in departments.data" :key="department.id"
                            class="py-5 flex justify-between">
                            <div class="flex">
                                <TeamIconCollection class="h-18 w-18" :iconName="department.svg_name" />
                                <div class="ml-5 my-auto w-full justify-start mr-6">
                                    <div class="flex my-auto">
                                        <p class="text-lg subpixel-antialiased text-gray-900">{{ department.name }}</p>
                                    </div>
                                </div>
                            </div>
                            <div class="flex">
                                <div class="flex mr-8">
                                    <div  class="my-auto -mr-3" v-for="user in department.users.slice(0,9)">
                                        <img class="h-9 w-9 rounded-full ring-2 ring-white"
                                             :src="user.profile_photo_url"
                                             alt=""/>
                                    </div>
                                    <div v-if="department.users.length >= 9" class="my-auto">
                                        <Menu as="div" class="relative">
                                            <div>
                                                <MenuButton class="flex items-center rounded-full focus:outline-none">
                                                    <ChevronDownIcon  class="ml-1 flex-shrink-0 h-9 w-9 flex my-auto items-center ring-2 ring-white font-semibold rounded-full shadow-sm text-white bg-black"></ChevronDownIcon>
                                                </MenuButton>
                                            </div>
                                            <transition enter-active-class="transition ease-out duration-100"
                                                        enter-from-class="transform opacity-0 scale-95"
                                                        enter-to-class="transform opacity-100 scale-100"
                                                        leave-active-class="transition ease-in duration-75"
                                                        leave-from-class="transform opacity-100 scale-100"
                                                        leave-to-class="transform opacity-0 scale-95">
                                                <MenuItems
                                                    class="z-40 absolute overflow-y-auto max-h-48 mt-2 w-72 mr-12 origin-top-right shadow-lg py-1 bg-primary ring-1 ring-black ring-opacity-5 focus:outline-none">
                                                    <MenuItem v-for="user in department.users" v-slot="{ active }">
                                                        <Link href="#"
                                                              :class="[active ? 'bg-primaryHover text-secondaryHover' : 'text-secondary', 'group flex items-center px-4 py-2 text-sm subpixel-antialiased']">
                                                            <img class="h-9 w-9 rounded-full"
                                                                 :src="user.profile_photo_url"
                                                                 alt=""/>
                                                            <span class="ml-4">
                                                                {{user.first_name}} {{user.last_name}}
                                                            </span>
                                                        </Link>
                                                    </MenuItem>
                                                </MenuItems>
                                            </transition>
                                        </Menu>
                                    </div>
                                </div>

                                <Menu as="div" class="my-auto relative">
                                    <div class="flex">
                                        <MenuButton
                                            class="flex">
                                            <DotsVerticalIcon class="mr-3 flex-shrink-0 h-6 w-6 text-gray-600 my-auto"
                                                              aria-hidden="true"/>
                                        </MenuButton>
                                        <div v-if="$page.props.can.show_hints && index === 0" class="absolute flex w-40 ml-6">
                                            <div>
                                            <SvgCollection svgName="arrowLeft" class="mt-1 ml-2"/>
                                            </div>
                                            <div class="flex">
                                                <span class="font-nanum ml-2 text-secondary tracking-tight tracking-tight text-lg">Bearbeite dein Team</span>
                                            </div>
                                        </div>
                                    </div>
                                    <transition enter-active-class="transition ease-out duration-100"
                                                enter-from-class="transform opacity-0 scale-95"
                                                enter-to-class="transform opacity-100 scale-100"
                                                leave-active-class="transition ease-in duration-75"
                                                leave-from-class="transform opacity-100 scale-100"
                                                leave-to-class="transform opacity-0 scale-95">
                                        <MenuItems
                                            class="origin-top-right absolute right-0 mr-4 mt-2 w-72 shadow-lg bg-zinc-800 ring-1 ring-black ring-opacity-5 divide-y divide-gray-100 focus:outline-none">
                                            <div class="py-1">
                                                <MenuItem v-slot="{ active }">
                                                    <a :href="getEditHref(department)"
                                                       :class="[active ? 'bg-primaryHover text-white' : 'text-secondary', 'group flex items-center px-4 py-2 text-sm subpixel-antialiased']">
                                                        <PencilAltIcon
                                                            class="mr-3 h-5 w-5 text-primaryText group-hover:text-white"
                                                            aria-hidden="true"/>
                                                        Team bearbeiten
                                                    </a>
                                                </MenuItem>
                                                <MenuItem v-slot="{ active }">
                                                    <a href="#" @click="deleteAllTeamMembers(department.id)"
                                                       :class="[active ? 'bg-primaryHover text-white' : 'text-secondary', 'group flex items-center px-4 py-2 text-sm subpixel-antialiased']">
                                                        <TrashIcon
                                                            class="mr-3 h-5 w-5 text-primaryText group-hover:text-white"
                                                            aria-hidden="true"/>
                                                        Alle Teammitglieder entfernen
                                                    </a>
                                                </MenuItem>
                                            </div>
                                        </MenuItems>
                                    </transition>
                                </Menu>

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
                    <div class="font-bold font-lexend text-primary tracking-wide text-2xl my-2">
                        Neues Team erstellen
                    </div>
                    <XIcon @click="closeAddTeamModal" class="h-5 w-5 right-0 top-0 mt-8 mr-5 absolute cursor-pointer"
                           aria-hidden="true"/>
                    <div class="text-secondary subpixel-antialiased">
                        Erstelle ein festes Team/Abteilung.
                    </div>
                    <div class="mt-12">
                        <div class="flex">
                            <Menu as="div" class=" relative">
                                <div>
                                    <MenuButton class="flex items-center rounded-full focus:outline-none">
                                        <ChevronDownIcon v-if="form.svg_name === ''"
                                                         class="ml-1 flex-shrink-0 mt-1 h-16 w-16 flex my-auto items-center rounded-full shadow-sm text-white bg-black"></ChevronDownIcon>
                                        <TeamIconCollection v-else class="h-16 w-16" :iconName="form.svg_name" />
                                    </MenuButton>
                                </div>
                                <transition enter-active-class="transition ease-out duration-100"
                                            enter-from-class="transform opacity-0 scale-95"
                                            enter-to-class="transform opacity-100 scale-100"
                                            leave-active-class="transition ease-in duration-75"
                                            leave-from-class="transform opacity-100 scale-100"
                                            leave-to-class="transform opacity-0 scale-95">
                                    <MenuItems
                                        class="z-40 origin-top-right absolute right-0 mt-2 shadow-lg py-1 bg-primary ring-1 ring-black ring-opacity-5 focus:outline-none">
                                        <MenuItem v-for="item in iconMenuItems"  v-slot="{ active }">
                                            <Link href="#" @click="form.svg_name = item.iconName"
                                                  :class="[active ? 'bg-primaryHover text-secondaryHover' : 'text-secondary', 'group flex items-center px-4 py-2 text-sm subpixel-antialiased']">
                                                <TeamIconCollection class="h-16 w-16" :iconName="item.iconName" />
                                            </Link>
                                        </MenuItem>
                                    </MenuItems>
                                </transition>
                            </Menu>
                            <div class="relative my-auto w-full ml-8 mr-12">
                                <input id="name" v-model="form.name" type="text" class="peer pl-0 h-12 w-full focus:border-t-transparent focus:border-primary focus:ring-0 border-l-0 border-t-0 border-r-0 border-b-2 border-gray-300 text-primary placeholder-secondary placeholder-transparent" placeholder="placeholder" />
                                <label for="name" class="absolute left-0 text-base -top-5 text-gray-600 text-sm -top-3.5 transition-all subpixel-antialiased focus:outline-none text-secondary peer-placeholder-shown:text-base peer-placeholder-shown:text-gray-400 peer-placeholder-shown:top-2 peer-focus:-top-3.5 peer-focus:text-sm ">Name</label>
                            </div>
                        </div>
                        <div class="mt-12">
                            <div class="font-bold font-lexend text-primary text-2xl my-2">
                                Nutzer*innen hinzufügen
                            </div>
                            <div class="text-secondary tracking-tight leading-6 subpixel-antialiased">
                                Tippe den Namen der Nutzer*innen ein, die du zum Team hinzufügen möchtest.
                            </div>
                            <div class="flex">
                                <!-- TODO: HIER MEILISEARCH mit Input verbinden -->
                                <div class="relative my-auto w-full ml-8 mr-12">
                                    <input id="userSearch" v-model="username" type="text" class="peer pl-0 h-12 w-full focus:border-t-transparent focus:border-primary focus:ring-0 border-l-0 border-t-0 border-r-0 border-b-2 border-gray-300 text-primary placeholder-secondary placeholder-transparent" placeholder="placeholder" />
                                    <label for="userSearch" class="absolute left-0 text-base -top-5 text-gray-600 text-sm -top-3.5 transition-all subpixel-antialiased focus:outline-none text-secondary peer-placeholder-shown:text-base peer-placeholder-shown:text-gray-400 peer-placeholder-shown:top-2 peer-focus:-top-3.5 peer-focus:text-sm ">Name</label>
                                </div>
                                <!-- Ganze Listbox + Hinzufügen Button kann weg wenn MEILISEARCH da, brauche nur zum zuweisen -->
                                <Listbox as="div" v-model="selected">
                                    <ListboxLabel class="block text-sm font-medium text-gray-700">
                                        Nutzer*in zum Hinzufügen auswählen
                                    </ListboxLabel>
                                    <div class="mt-1 relative">
                                        <ListboxButton
                                            class="relative w-full bg-white border border-gray-300 rounded-md shadow-sm pl-3 pr-10 py-2 text-left cursor-default focus:outline-none focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                                        <span class="flex items-center">
                                            <img :src="selected.profile_photo_url" alt=""
                                                 class="flex-shrink-0 h-6 w-6 rounded-full"/>
                                            <span class="ml-3 block truncate">
                                                {{ selected.first_name }} {{ selected.last_name }}</span>
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
                                                <ListboxOption as="template" v-for="user in users" :key="user.email"
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
                        </div>
                        <div class="mt-4">
                            <div class="flex">
                            </div>
                            <span v-for="(user,index) in form.assigned_users"
                                  class="flex mt-4 mr-1 rounded-full items-center font-bold text-primary">
                            <div class="flex items-center">
                                <img class="flex h-11 w-11 rounded-full"
                                     :src="user.profile_photo_url"
                                     alt=""/>
                                <span class="flex ml-4">
                                {{ user.first_name }} {{ user.last_name }}
                                    </span>
                            </div>
                            <button type="button" @click="deleteUserFromTeam(index)">
                                <span class="sr-only">User aus Team entfernen</span>
                                <XCircleIcon class="ml-2 mt-1 h-5 w-5 hover:text-error "/>
                            </button>
                        </span>
                        </div>
                        <button
                            :class="[this.form.name === '' ? 'bg-secondary': 'bg-primary hover:bg-primaryHover focus:outline-none']"
                            class="mt-8 inline-flex items-center px-20 py-3 border bg-primary hover:bg-primaryHover focus:outline-none border-transparent text-base font-bold text-xl uppercase shadow-sm text-secondaryHover"
                            @click="addTeam"
                            :disabled="this.form.name === ''">
                            Team erstellen
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
import {DotsVerticalIcon,ChevronDownIcon ,InformationCircleIcon, XIcon, PencilAltIcon, TrashIcon} from '@heroicons/vue/outline'
import {ChevronUpIcon, PlusSmIcon, CheckIcon, SelectorIcon, XCircleIcon} from '@heroicons/vue/solid'
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
import Button from "@/Jetstream/Button";
import JetButton from "@/Jetstream/Button";
import JetDialogModal from "@/Jetstream/DialogModal";
import JetInput from "@/Jetstream/Input";
import JetInputError from "@/Jetstream/InputError";
import JetSecondaryButton from "@/Jetstream/SecondaryButton";
import Checkbox from "@/Layouts/Components/Checkbox";
import {useForm} from "@inertiajs/inertia-vue3";
import SvgCollection from "@/Layouts/Components/SvgCollection";
import TeamIconCollection from "@/Layouts/Components/TeamIconCollection";

const iconMenuItems = [
    {iconName: 'departmentImagePlaceholder'},
    {iconName: 'teamIconTech'},
]

export default defineComponent({
    components: {
        TeamIconCollection,
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
        XIcon,
        PencilAltIcon,
        TrashIcon,
        XCircleIcon
    },
    props: ['departments', 'users'],
    methods: {
        openAddTeamModal() {
            this.addingTeam = true;
            this.showX = [];
        },
        closeAddTeamModal() {
            this.addingTeam = false;
            this.showX = [];
            this.form.assigned_users = [];
            this.form.name = "";
            this.form.svg_name = "";
        },
        addUserToAssignedUsersArray() {
            if (!this.form.assigned_users.includes(this.selected)) {
                this.form.assigned_users.push(this.selected);
            }
        },
        deleteUserFromTeam(index) {
            this.form.assigned_users.splice(index, 1);
        },
        deleteUserFromAssignedUsersArray(index) {
            this.form.assigned_users.splice(index, 1);
        },
        addTeam() {
            this.form.post(route('departments.store'), {})
            this.closeAddTeamModal();

        },
        deleteAllTeamMembers(teamId){
            this.deleteMembersForm.patch(route('departments.edit',{department: teamId}));
        },
        getEditHref(department) {
            return route('departments.show', {department: department.id});
        }
    },
    data() {
        return {
            addingTeam: false,
            showX: [],
            form: useForm({
                svg_name: "",
                name: "",
                assigned_users: [],

            }),
            deleteMembersForm: this.$inertia.form({
                _method: 'PUT',
                users: [],
            }),
        }
    },
    setup(props) {
        const selected = ref(props.users[0])

        return {
            selected,
            iconMenuItems
        }
    }
})
</script>
