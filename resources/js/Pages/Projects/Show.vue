<template>
    <app-layout title="Teamprofil">
        <div class="max-w-screen-2xl my-12 ml-20 mr-10 flex flex-row">
            <div class="flex w-8/12 flex-col">
                <div class="flex">
                    <h2 class="flex font-bold font-lexend text-3xl">{{ project.name }}</h2>
                    <Menu as="div" class="my-auto relative">
                        <div class="flex">
                            <MenuButton
                                class="flex ml-6">
                                <DotsVerticalIcon class="mr-3 flex-shrink-0 h-6 w-6 text-gray-600 my-auto"
                                                  aria-hidden="true"/>
                            </MenuButton>
                            <div v-if="$page.props.can.show_hints" class="absolute flex w-48 ml-12">
                                <div>
                                    <SvgCollection svgName="arrowLeft" class="mt-1 ml-2"/>
                                </div>
                                <div class="flex">
                                    <span class="font-nanum ml-2 text-secondary tracking-tight tracking-tight text-lg">Bearbeite die Basisdaten</span>
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
                                class="origin-top-left absolute left-0 mr-4 mt-2 w-72 shadow-lg bg-zinc-800 ring-1 ring-black ring-opacity-5 divide-y divide-gray-100 focus:outline-none">
                                <div class="py-1">
                                    <MenuItem v-slot="{ active }">
                                        <a @click="openEditProjectModal"
                                           :class="[active ? 'bg-primaryHover text-white' : 'text-secondary', 'group flex items-center px-4 py-2 text-sm subpixel-antialiased']">
                                            <PencilAltIcon
                                                class="mr-3 h-5 w-5 text-primaryText group-hover:text-white"
                                                aria-hidden="true"/>
                                            Basisdaten bearbeiten
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
                                        <a @click=""
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
                <div class="mt-2 text-secondary">
                    zuletzt geändert:
                </div>
                <div class="mt-2 text-secondary">
                    {{ project.description }}
                </div>
                <div class="mt-6 text-secondary">
                    Kostenträger: {{ project.cost_center ? project.cost_center : 'noch nicht definiert' }} | Anzahl
                    Teilnehmer*innen:
                    {{ project.number_of_participants ? project.number_of_participants : 'noch nicht definiert' }}
                </div>
                <!--
                <div>
                    Kategorie: {{ project.category ? project.category : 'noch nicht definiert'}} | Genre: {{ project.genre ? project.genre : 'noch nicht definiert' }} | Bereich: {{ project.field ? project.field : 'noch nicht definiert'}}
                </div>
                -->
                <h3 class="text-xl mt-6 mb-8 leading-6 font-bold font-lexend text-gray-900">Wann und wo?</h3>
                </div>
                <div class="flex flex-wrap">
                    <div class="flex">
                    <h2 class="font-bold font-lexend text-2xl">Projektteam</h2>
                    <div class="cursor-pointer" @click="">
                        <DotsVerticalIcon class="ml-2 mr-1 mt-2 flex-shrink-0 h-6 w-6 text-gray-600"
                                          aria-hidden="true"/>
                    </div>
                    <div>
                        <div v-if="$page.props.can.show_hints" class="absolute flex w-48 mt-2">
                            <div>
                                <SvgCollection svgName="arrowLeft" class="mt-1"/>
                            </div>
                            <div class="flex">
                                <span class="font-nanum ml-2 text-secondary tracking-tight tracking-tight text-lg">Stelle dein Team zusammen</span>
                            </div>
                        </div>
                    </div>
                    </div>
                    <div class="flex w-full">
                    <span class="flex text-secondary -mt-16">Projektleitung</span>
                </div>

                </div>



        </div>
        <!-- Projekt erstellen Modal-->
        <jet-dialog-modal :show="editingProject" @close="closeEditProjectModal">
            <template #content>
                <div class="mx-4">
                    <div class="font-bold font-lexend text-primary tracking-wide text-2xl my-2">
                        Basisdaten bearbeiten
                    </div>
                    <XIcon @click="closeEditProjectModal"
                           class="h-5 w-5 right-0 top-0 mt-8 mr-5 absolute cursor-pointer"
                           aria-hidden="true"/>
                    <div class="mt-12">
                        <div class="flex">
                            <div class="relative flex w-full mr-4">
                                <input id="first_name" v-model="form.name" type="text"
                                       class="peer pl-0 h-12 w-full focus:border-t-transparent focus:border-primary focus:ring-0 border-l-0 border-t-0 border-r-0 border-b-2 border-gray-300 text-xl font-bold text-primary placeholder-secondary placeholder-transparent"
                                       placeholder="placeholder"/>
                                <label for="first_name"
                                       class="absolute left-0 text-base -top-4 text-gray-600 -top-6 transition-all subpixel-antialiased focus:outline-none text-secondary peer-placeholder-shown:text-base peer-placeholder-shown:text-gray-400 peer-placeholder-shown:top-2 peer-focus:-top-3.5 peer-focus:text-sm ">Projektname</label>
                            </div>
                        </div>
                        <div class="mt-8 mr-4">
                                            <textarea
                                                placeholder="Kurzbeschreibung"
                                                v-model="form.description" rows="4"
                                                class="focus:border-primary placeholder-secondary border-2 w-full font-semibold border border-gray-300 "/>
                        </div>
                        <div class="mt-4 grid grid-cols-1 gap-y-6 gap-x-4 sm:grid-cols-6">
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
                                        <span
                                            class="absolute inset-y-0 right-0 flex items-center pr-2 pointer-events-none">
                                     <ChevronDownIcon class="h-5 w-5 text-gray-400" aria-hidden="true"/>
                                    </span>
                                    </ListboxButton>

                                    <transition leave-active-class="transition ease-in duration-100"
                                                leave-from-class="opacity-100" leave-to-class="opacity-0">
                                        <ListboxOptions
                                            class="absolute z-10 mt-1 w-full bg-primary shadow-lg max-h-60 rounded-md py-1 text-base ring-1 ring-black ring-opacity-5 overflow-auto focus:outline-none sm:text-sm">
                                            <ListboxOption as="template"
                                                           v-for="participantNumber in number_of_participants"
                                                           :key="participantNumber.number"
                                                           :value="participantNumber.number"
                                                           v-slot="{ active, selected }">
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
                            @click="editProject"
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

import {useForm} from "@inertiajs/inertia-vue3";
import AppLayout from '@/Layouts/AppLayout.vue';
import {
    Listbox,
    ListboxButton,
    ListboxOption,
    ListboxOptions,
    Menu,
    MenuButton,
    MenuItem,
    MenuItems
} from "@headlessui/vue";
import {PencilAltIcon, TrashIcon, XIcon, DuplicateIcon} from "@heroicons/vue/outline";
import {CheckIcon, ChevronDownIcon, DotsVerticalIcon, XCircleIcon} from "@heroicons/vue/solid";
import SvgCollection from "@/Layouts/Components/SvgCollection";
import JetButton from "@/Jetstream/Button";
import JetDialogModal from "@/Jetstream/DialogModal";
import JetInput from "@/Jetstream/Input";
import JetInputError from "@/Jetstream/InputError";
import TeamIconCollection from "@/Layouts/Components/TeamIconCollection";

const number_of_participants = [
    {number: '100-1000'},
    {number: '1000-10000'},
]

export default {
    name: "ProjectShow",
    props: ['project'],
    components: {
        TeamIconCollection,
        AppLayout,
        Menu,
        MenuButton,
        MenuItem,
        MenuItems,
        XIcon,
        PencilAltIcon,
        TrashIcon,
        DotsVerticalIcon,
        SvgCollection,
        XCircleIcon,
        JetButton,
        JetDialogModal,
        JetInput,
        JetInputError,
        CheckIcon,
        ChevronDownIcon,
        DuplicateIcon,
        Listbox,
        ListboxButton,
        ListboxOption,
        ListboxOptions,
    },
    data() {
        return {
            editingProject: false,
            selectedParticipantNumber: this.project.number_of_participants,
            form: useForm({
                name: this.project.name,
                description: this.project.description,
                cost_center: this.project.cost_center,
                number_of_participants: this.project.number_of_participants
            }),
        }
    },
    methods: {
        openEditProjectModal() {
            this.editingProject = true;
        },
        closeEditProjectModal() {
            this.editingProject = false;
        },
        editProject() {
            this.form.number_of_participants = this.selectedParticipantNumber;
            this.form.patch(route('projects.update', {project: this.project.id}));
            this.closeEditProjectModal();
        },
    },
    setup() {
        return {
            number_of_participants
        }
    }
}
</script>

<style scoped>

</style>
