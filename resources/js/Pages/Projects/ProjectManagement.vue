<template>
    <app-layout title="Meine Projekte">
        <div class="py-4">
            <div class="max-w-screen-lg mb-40 my-12 flex flex-row ml-20 mr-40">
                <div class="flex flex-1 flex-wrap">
                    <div class="w-full flex my-auto justify-between">
                        <div class="flex">
                            <h2 class="text-2xl flex">Meine Projekte</h2>
                            <button @click="openAddProjectModal" type="button"
                                    class="flex my-auto ml-6 items-center border border-transparent rounded-full shadow-sm text-white bg-primary hover:bg-primaryHover focus:outline-none">
                                <PlusSmIcon class="h-5 w-5" aria-hidden="true"/>
                            </button>
                            <div v-if="$page.props.can.show_hints" class="flex mt-1">
                                <SvgCollection svgName="arrowLeft" class="mt-1 ml-2"/>
                                <span
                                    class="font-nanum text-secondary tracking-tight ml-1 my-auto tracking-tight text-lg">Lege neue Projekte an</span>
                            </div>
                        </div>
                        <div class="flex items-center">

                            <div class="inset-y-0 mr-3 pointer-events-none">
                                <SearchIcon class="h-5 w-5" aria-hidden="true"/>
                            </div>
                        </div>
                    </div>
                    <ul role="list" class="mt-5 w-full">
                        <li v-if="projects.data.length > 0" v-for="(project,index) in projects.data" :key="project.id"
                            class="py-5 flex justify-between">
                            <div class="flex">
                                <div class="ml-5 my-auto w-full justify-start mr-6">
                                    <div class="flex my-auto">
                                        <p class="text-lg subpixel-antialiased text-gray-900">{{ project.name }}</p>
                                    </div>
                                </div>
                            </div>
                            <div class="flex">
                                <div class="flex mr-8">
                                    <div class="my-auto -mr-3" v-for="user in project.users.slice(0,9)">
                                        <img class="h-9 w-9 rounded-full ring-2 ring-white"
                                             :src="user.profile_photo_url"
                                             alt=""/>
                                    </div>
                                    <div v-if="project.users.length >= 9" class="my-auto">
                                        <Menu as="div" class="relative">
                                            <div>
                                                <MenuButton class="flex items-center rounded-full focus:outline-none">
                                                    <ChevronDownIcon
                                                        class="ml-1 flex-shrink-0 h-9 w-9 flex my-auto items-center ring-2 ring-white font-semibold rounded-full shadow-sm text-white bg-black"></ChevronDownIcon>
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
                                                    <MenuItem v-for="user in project.users" v-slot="{ active }">
                                                        <Link href="#"
                                                              :class="[active ? 'bg-primaryHover text-secondaryHover' : 'text-secondary', 'group flex items-center px-4 py-2 text-sm subpixel-antialiased']">
                                                            <img class="h-9 w-9 rounded-full"
                                                                 :src="user.profile_photo_url"
                                                                 alt=""/>
                                                            <span class="ml-4">
                                                                {{ user.first_name }} {{ user.last_name }}
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
                                        <div v-if="$page.props.can.show_hints && index === 0"
                                             class="absolute flex w-40 ml-6">
                                            <div>
                                                <SvgCollection svgName="arrowLeft" class="mt-1 ml-2"/>
                                            </div>
                                            <div class="flex">
                                                <span
                                                    class="font-nanum ml-2 text-secondary tracking-tight tracking-tight text-lg">Bearbeite die Projekte</span>
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
                                                    <a :href="getEditHref(project)"
                                                       :class="[active ? 'bg-primaryHover text-white' : 'text-secondary', 'group flex items-center px-4 py-2 text-sm subpixel-antialiased']">
                                                        <PencilAltIcon
                                                            class="mr-3 h-5 w-5 text-primaryText group-hover:text-white"
                                                            aria-hidden="true"/>
                                                        Bearbeiten
                                                    </a>
                                                </MenuItem>
                                                <MenuItem v-slot="{ active }">
                                                    <a href="#" @click=""
                                                       :class="[active ? 'bg-primaryHover text-white' : 'text-secondary', 'group flex items-center px-4 py-2 text-sm subpixel-antialiased']">
                                                        <DuplicateIcon
                                                            class="mr-3 h-5 w-5 text-primaryText group-hover:text-white"
                                                            aria-hidden="true"/>
                                                        Duplizieren
                                                    </a>
                                                </MenuItem>
                                                <MenuItem v-slot="{ active }">
                                                    <a href="#" @click=""
                                                       :class="[active ? 'bg-primaryHover text-white' : 'text-secondary', 'group flex items-center px-4 py-2 text-sm subpixel-antialiased']">
                                                        <TrashIcon
                                                            class="mr-3 h-5 w-5 text-primaryText group-hover:text-white"
                                                            aria-hidden="true"/>
                                                        In den Papierkorb legen
                                                    </a>
                                                </MenuItem>
                                            </div>
                                        </MenuItems>
                                    </transition>
                                </Menu>

                            </div>
                            <div>
                                <span>zuletzt geändert:</span>
                            </div>
                        </li>
                    </ul>
                </div>


            </div>
        </div>
        <!-- Projekt erstellen Modal-->
        <jet-dialog-modal :show="addingProject" @close="closeAddProjectModal">
            <template #content>
                <div class="mx-4">
                    <div class="font-bold font-lexend text-primary tracking-wide text-2xl my-2">
                        Neues Projekt
                    </div>
                    <XIcon @click="closeAddProjectModal" class="h-5 w-5 right-0 top-0 mt-8 mr-5 absolute cursor-pointer"
                           aria-hidden="true"/>
                    <div class="mt-12">
                        <div class="flex">
                            <div class="relative flex w-full mr-4">
                                <input id="first_name" v-model="form.name" type="text" class="peer pl-0 h-12 w-full focus:border-t-transparent focus:border-primary focus:ring-0 border-l-0 border-t-0 border-r-0 border-b-2 border-gray-300 text-xl font-bold text-primary placeholder-secondary placeholder-transparent" placeholder="placeholder" />
                                <label for="first_name" class="absolute left-0 text-base -top-4 text-gray-600 -top-6 transition-all subpixel-antialiased focus:outline-none text-secondary peer-placeholder-shown:text-base peer-placeholder-shown:text-gray-400 peer-placeholder-shown:top-2 peer-focus:-top-3.5 peer-focus:text-sm ">Projektname</label>
                            </div>
                        </div>
                            <div class="mt-8 mr-4">
                                            <textarea
                                                placeholder="Kurzbeschreibung"
                                                v-model="form.description" rows="4"
                                                class="focus:border-primary placeholder-secondary border-2 w-full font-semibold border border-gray-300 "/>
                            </div>

                        <div v-on:click="showDetails = !showDetails">
                            <h2 class="text-sm flex text-primary font-semibold cursor-pointer mt-4 ">
                                Weitere Angaben
                                <ChevronUpIcon v-if="showDetails"
                                               class=" ml-1 mr-3 flex-shrink-0 mt-1 h-4 w-4"></ChevronUpIcon>
                                <ChevronDownIcon v-else class=" ml-1 mr-3 flex-shrink-0 mt-1 h-4 w-4"></ChevronDownIcon>
                            </h2>
                        </div>
                        <div v-if="showDetails" class="mt-6 grid grid-cols-1 gap-y-6 gap-x-4 sm:grid-cols-6">
                        <div class="sm:col-span-3">
                            <div class="mt-1">
                                <input type="text" v-model="form.cost_center" placeholder="Kostenträger eintragen"
                                       class="text-primary focus:border-primary border-2 w-full font-semibold border-gray-300 "/>
                            </div>
                        </div>
                        <Listbox as="div" class="sm:col-span-3 mt-1" v-model="selectedParticipantNumber">
                            <div class="relative">
                                <ListboxButton
                                    class="bg-white relative  border-2 w-full border border-gray-300 font-semibold shadow-sm pl-3 pr-10 py-2 text-left cursor-default focus:outline-none focus:ring-1 focus:ring-primary focus:border-primary sm:text-sm">
                                    <span class="block truncate">{{ selectedParticipantNumber }}</span>
                                    <span v-if="selectedParticipantNumber === ''" class="block truncate">Anzahl Teilnehmer*innen</span>
                                    <span class="absolute inset-y-0 right-0 flex items-center pr-2 pointer-events-none">
                                     <ChevronDownIcon class="h-5 w-5 text-gray-400" aria-hidden="true"/>
                                    </span>
                                </ListboxButton>

                                <transition leave-active-class="transition ease-in duration-100"
                                            leave-from-class="opacity-100" leave-to-class="opacity-0">
                                    <ListboxOptions
                                        class="absolute z-10 mt-1 w-full bg-primary shadow-lg max-h-60 rounded-md py-1 text-base ring-1 ring-black ring-opacity-5 overflow-auto focus:outline-none sm:text-sm">
                                        <ListboxOption as="template" v-for="participantNumber in number_of_participants" :key="participantNumber.number"
                                                       :value="participantNumber.number" v-slot="{ active, selected }">
                                            <li :class="[active ? 'bg-primaryHover text-white' : 'text-secondary', 'group flex items-center py-2 pl-3 pr-9 text-sm subpixel-antialiased']">
                                            <span
                                                :class="[selected ? 'font-semibold' : 'font-normal', 'block truncate']">
                                                {{ participantNumber.number }}
                                            </span>
                                                <span v-if="selected"
                                                      :class="[active ? 'bg-primaryHover text-white' : 'text-secondary', 'group flex items-center text-sm subpixel-antialiased']">
                                                      <CheckIcon class="h-5 w-5" aria-hidden="true"/>
                                                </span>
                                            </li>
                                        </ListboxOption>
                                    </ListboxOptions>
                                </transition>
                            </div>
                        </Listbox>
                        </div>
                        <button
                            :class="[this.form.name === '' ? 'bg-secondary': 'bg-primary hover:bg-primaryHover focus:outline-none']"
                            class="mt-8 inline-flex items-center px-20 py-3 border bg-primary hover:bg-primaryHover focus:outline-none border-transparent text-base font-bold text-xl uppercase shadow-sm text-secondaryHover"
                            @click="addTeam"
                            :disabled="this.form.name === ''">
                            Anlegen
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
    InformationCircleIcon,
    XIcon,
    PencilAltIcon,
    TrashIcon,
    DuplicateIcon
} from '@heroicons/vue/outline'
import {ChevronUpIcon, PlusSmIcon, CheckIcon, SelectorIcon, XCircleIcon} from '@heroicons/vue/solid'
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
import TeamIconCollection from "@/Layouts/Components/TeamIconCollection";

const number_of_participants = [
    {number: '100-1000'},
    {number: '1000-10000'},
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
        XCircleIcon,
        DuplicateIcon
    },
    props: ['projects', 'users'],
    methods: {
        openAddProjectModal() {
            this.addingProject = true;
        },
        closeAddProjectModal() {
            this.addingProject = false;
            this.form.name = "";
            this.form.description = "";
            this.form.cost_center = "";
            this.form.number_of_participants = "";
            this.selectedParticipantNumber = "";
        },
        addTeam() {
            this.form.number_of_participants = this.selectedParticipantNumber;

            this.form.post(route('projects.store'), {})
            this.closeAddProjectModal();
        },
        getEditHref(project) {
            return route('projects.show', {project: project.id});
        }
    },
    data() {
        return {
            addingProject: false,
            showDetails:false,
            selectedParticipantNumber: "",
            form: useForm({
                name: "",
                description: "",
                cost_center:"",
                number_of_participants:""
            }),
        }
    },
    setup() {

        return {
            number_of_participants
        }
    }
})
</script>
