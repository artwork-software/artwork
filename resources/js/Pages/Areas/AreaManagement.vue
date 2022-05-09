<template>
    <app-layout title="Dashboard">
        <div class="py-4">
            <div class="max-w-screen-lg mb-40 my-12 flex flex-row ml-20 mr-40">
                <div class="flex flex-1 flex-wrap">
                    <div class="w-full flex my-auto justify-between">
                        <div class="flex flex-wrap">
                            <div class="flex flex-wrap">
                                <h2 class="text-3xl font-lexend font-black text-primary flex">Räume & Areale</h2>
                                <div class="flex w-full mt-6">
                                    <div class="">
                                        <button @click="openAddAreaModal()" type="button"
                                                class="flex border border-transparent rounded-full shadow-sm text-white bg-primary hover:bg-primaryHover focus:outline-none">
                                            <PlusSmIcon class="h-5 w-5" aria-hidden="true"/>
                                        </button>
                                    </div>
                                    <div v-if="$page.props.can.show_hints" class="flex">
                                        <SvgCollection svgName="arrowLeft" class="ml-2"/>
                                        <span
                                            class="font-nanum text-secondary tracking-tight ml-1 tracking-tight text-xl">Lege neue Räume an</span>
                                    </div>
                                </div>
                            </div>
                            <div class="flex flex-wrap mt-10">
                                <div v-for="area in areas.data"
                                     class="flex w-full bg-white my-2 border border-gray-200">
                                    <button class="bg-black flex" @click="area.hidden = !area.hidden">
                                        <ChevronUpIcon v-if="area.hidden !== true"
                                                       class="h-6 w-6 text-white my-auto"></ChevronUpIcon>
                                        <ChevronDownIcon v-else
                                                         class="h-6 w-6 text-white my-auto"></ChevronDownIcon>
                                    </button>
                                    <div class="flex mt-8 w-full ml-4 flex-wrap p-4">
                                        <div class="flex justify-between w-full">
                                            <div class="my-auto">
                                        <span class="text-2xl leading-6 font-bold font-lexend text-gray-900">
                                        {{ area.name }}
                                        </span>
                                            </div>
                                            <div class="flex items-center">
                                                <Menu as="div" class="my-auto relative">
                                                    <div class="flex">
                                                        <MenuButton
                                                            class="flex ml-6">
                                                            <DotsVerticalIcon
                                                                class="mr-3 flex-shrink-0 h-6 w-6 text-gray-600 my-auto"
                                                                aria-hidden="true"/>
                                                        </MenuButton>
                                                    </div>
                                                    <transition
                                                        enter-active-class="transition ease-out duration-100"
                                                        enter-from-class="transform opacity-0 scale-95"
                                                        enter-to-class="transform opacity-100 scale-100"
                                                        leave-active-class="transition ease-in duration-75"
                                                        leave-from-class="transform opacity-100 scale-100"
                                                        leave-to-class="transform opacity-0 scale-95">
                                                        <MenuItems
                                                            class="origin-top-right absolute right-0 w-56 shadow-lg bg-zinc-800 ring-1 ring-black ring-opacity-5 divide-y divide-gray-100 focus:outline-none">
                                                            <div class="py-1">
                                                                <MenuItem v-slot="{ active }">
                                                                    <a @click="openEditChecklistModal(checklist)"
                                                                       :class="[active ? 'bg-primaryHover text-white' : 'text-secondary', 'cursor-pointer group flex items-center px-4 py-2 text-sm subpixel-antialiased']">
                                                                        <PencilAltIcon
                                                                            class="mr-3 h-5 w-5 text-primaryText group-hover:text-white"
                                                                            aria-hidden="true"/>
                                                                        Bearbeiten
                                                                    </a>
                                                                </MenuItem>
                                                                <MenuItem v-slot="{ active }">
                                                                    <a @click="checkAllTasks(checklist.tasks)"
                                                                       :class="[active ? 'bg-primaryHover text-white' : 'text-secondary', 'cursor-pointer group flex items-center px-4 py-2 text-sm subpixel-antialiased']">
                                                                        <PencilAltIcon
                                                                            class="mr-3 h-5 w-5 text-primaryText group-hover:text-white"
                                                                            aria-hidden="true"/>
                                                                        Alle Aufgaben als erledigt markieren
                                                                    </a>
                                                                </MenuItem>
                                                                <MenuItem v-slot="{ active }">
                                                                    <a @click=""
                                                                       :class="[active ? 'bg-primaryHover text-white' : 'text-secondary', 'cursor-pointer group flex items-center px-4 py-2 text-sm subpixel-antialiased']">
                                                                        <PencilAltIcon
                                                                            class="mr-3 h-5 w-5 text-primaryText group-hover:text-white"
                                                                            aria-hidden="true"/>
                                                                        Als Vorlage speichern
                                                                    </a>
                                                                </MenuItem>
                                                                <MenuItem v-slot="{ active }">
                                                                    <a href="#"
                                                                       @click="duplicateChecklist(checklist)"
                                                                       :class="[active ? 'bg-primaryHover text-white' : 'text-secondary', 'cursor-pointer group flex items-center px-4 py-2 text-sm subpixel-antialiased']">
                                                                        <DuplicateIcon
                                                                            class="mr-3 h-5 w-5 text-primaryText group-hover:text-white"
                                                                            aria-hidden="true"/>
                                                                        Duplizieren
                                                                    </a>
                                                                </MenuItem>
                                                                <MenuItem v-slot="{ active }">
                                                                    <a @click="openDeleteChecklistModal(checklist)"
                                                                       :class="[active ? 'bg-primaryHover text-white' : 'text-secondary', 'cursor-pointer group flex items-center px-4 py-2 text-sm subpixel-antialiased']">
                                                                        <TrashIcon
                                                                            class="mr-3 h-5 w-5 text-primaryText group-hover:text-white"
                                                                            aria-hidden="true"/>
                                                                        Löschen
                                                                    </a>
                                                                </MenuItem>
                                                            </div>
                                                        </MenuItems>
                                                    </transition>
                                                </Menu>
                                            </div>
                                        </div>
                                        <div class="flex w-full mt-6" v-if="!area.hidden">
                                            <div class="">
                                                <button @click="openAddRoomModal(area)" type="button"
                                                        class="flex border border-transparent rounded-full shadow-sm text-white bg-primary hover:bg-primaryHover focus:outline-none">
                                                    <PlusSmIcon class="h-5 w-5" aria-hidden="true"/>
                                                </button>
                                            </div>
                                            <div v-if="$page.props.can.show_hints" class="flex">
                                                <SvgCollection svgName="arrowLeft" class="ml-2"/>
                                                <span
                                                    class="font-nanum text-secondary tracking-tight ml-1 tracking-tight text-xl">Lege neue Räume an</span>
                                            </div>
                                        </div>
                                        {{ area.rooms }}
                                        <div class="mt-6 mb-12" v-if="!area.hidden">
                                            <draggable ghost-class="opacity-50"
                                                       key="draggableKey"
                                                       item-key="id" :list="area.rooms"
                                                       @start="dragging=true" @end="dragging=false"
                                                       @change="updateRoomOrder(area.rooms)">
                                                <template #item="{element}" :key="element.id">
                                                    <div class="flex" @mouseover="showMenu = element.id"
                                                         :key="element.id"
                                                         @mouseout="showMenu = null">
                                                        <div class="flex mt-6 flex-wrap w-full" :key="element.id"
                                                             :class="dragging? 'cursor-grabbing' : 'cursor-grab'">
                                                            <div class="flex w-full">
                                                                <div class="flex">
                                                                    <p class="ml-4 my-auto text-lg font-black text-sm"
                                                                       :class="element.done ? 'text-secondary' : 'text-primary'">
                                                                        {{ element.name }}</p>
                                                                    <div class="ml-10 text-secondary">
                                                                        angelegt am {{ element.created_at }} von
                                                                        <!-- TODO: HIER PROFILBILD ANLEGER -->
                                                                    </div>
                                                                </div>
                                                                <Menu as="div" class="my-auto relative"
                                                                      :key="element.id"
                                                                      v-show="showMenu === element.id">
                                                                    <div class="flex">
                                                                        <MenuButton
                                                                            class="flex ml-6">
                                                                            <DotsVerticalIcon
                                                                                class="mr-3 flex-shrink-0 h-6 w-6 text-gray-600 my-auto"
                                                                                aria-hidden="true"/>
                                                                        </MenuButton>
                                                                    </div>
                                                                    <transition
                                                                        enter-active-class="transition ease-out duration-100"
                                                                        enter-from-class="transform opacity-0 scale-95"
                                                                        enter-to-class="transform opacity-100 scale-100"
                                                                        leave-active-class="transition ease-in duration-75"
                                                                        leave-from-class="transform opacity-100 scale-100"
                                                                        leave-to-class="transform opacity-0 scale-95">
                                                                        <MenuItems
                                                                            class="origin-top-right absolute right-0 w-56 shadow-lg bg-zinc-800 ring-1 ring-black ring-opacity-5 divide-y divide-gray-100 focus:outline-none">
                                                                            <div class="py-1">
                                                                                <MenuItem v-slot="{ active }">
                                                                                <span
                                                                                    @click="openEditTaskModal(element)"
                                                                                    :class="[active ? 'bg-primaryHover text-white' : 'text-secondary', 'cursor-pointer group flex items-center px-4 py-2 text-sm subpixel-antialiased']">
                                                                                    <PencilAltIcon
                                                                                        class="mr-3 h-5 w-5 text-primaryText group-hover:text-white"
                                                                                        aria-hidden="true"/>
                                                                                    Bearbeiten
                                                                                </span>
                                                                                </MenuItem>
                                                                                <MenuItem v-slot="{ active }">
                                                                                    <a @click="deleteTask(element)"
                                                                                       :class="[active ? 'bg-primaryHover text-white' : 'text-secondary', 'cursor-pointer group flex items-center px-4 py-2 text-sm subpixel-antialiased']">
                                                                                        <TrashIcon
                                                                                            class="mr-3 h-5 w-5 text-primaryText group-hover:text-white"
                                                                                            aria-hidden="true"/>
                                                                                        Löschen
                                                                                    </a>
                                                                                </MenuItem>
                                                                            </div>
                                                                        </MenuItems>
                                                                    </transition>
                                                                </Menu>
                                                            </div>

                                                        </div>

                                                    </div>
                                                </template>
                                            </draggable>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </app-layout>
    <!-- Areal Hinzufügen-->
    <jet-dialog-modal :show="showAddAreaModal" @close="closeAddAreaModal">
        <template #content>
            <div class="mx-3">
                <div class="font-bold font-lexend text-primary text-3xl my-2">
                    Neues Areal
                </div>
                <XIcon @click="closeAddAreaModal"
                       class="h-5 w-5 right-0 top-0 mt-8 mr-5 absolute text-secondary cursor-pointer"
                       aria-hidden="true"/>
                <div class="mt-4">
                    <div class="flex mt-10 relative">
                        <input id="areaName" v-model="newAreaForm.name" type="text"
                               class="peer pl-0 h-12 w-full focus:border-t-transparent focus:border-primary focus:ring-0 border-l-0 border-t-0 border-r-0 border-b-2 border-gray-300 text-primary placeholder-secondary placeholder-transparent"
                               placeholder="placeholder"/>
                        <label for="areaName"
                               class="absolute left-0 text-base -top-4 text-gray-600 text-sm -top-3.5 transition-all subpixel-antialiased focus:outline-none text-secondary peer-placeholder-shown:text-base peer-placeholder-shown:text-gray-400 peer-placeholder-shown:top-2 peer-focus:-top-3.5 peer-focus:text-sm ">Name
                            des Areals
                        </label>
                        <jet-input-error :message="newAreaForm.error" class="mt-2"/>
                    </div>

                    <button :class="[newAreaForm.name.length === 0 ?
                    'bg-secondary': 'bg-primary hover:bg-primaryHover focus:outline-none']"
                            class="mt-4 flex items-center px-20 py-3 border border-transparent
                            text-base font-bold uppercase shadow-sm text-secondaryHover"
                            @click="addArea"
                            :disabled="newAreaForm.name.length === 0">
                        Anlegen
                    </button>
                </div>
            </div>
        </template>
    </jet-dialog-modal>
    <!-- Raum Hinzufügen-->
    <jet-dialog-modal :show="showAddRoomModal" @close="closeAddRoomModal">
        <template #content>
            <div class="mx-3">
                <div class="font-bold font-lexend text-primary text-3xl my-2">
                    Neuer Raum
                </div>
                <XIcon @click="closeAddRoomModal"
                       class="h-5 w-5 right-0 top-0 mt-8 mr-5 absolute text-secondary cursor-pointer"
                       aria-hidden="true"/>
                <div class="mt-4">
                    <div class="flex mt-10 relative">
                        <input id="roomName" v-model="newRoomForm.name" type="text"
                               class="peer pl-0 h-12 w-full focus:border-t-transparent focus:border-primary focus:ring-0 border-l-0 border-t-0 border-r-0 border-b-2 border-gray-300 text-primary placeholder-secondary placeholder-transparent"
                               placeholder="placeholder"/>
                        <label for="roomName"
                               class="absolute left-0 text-base -top-4 text-gray-600 text-sm -top-3.5 transition-all subpixel-antialiased focus:outline-none text-secondary peer-placeholder-shown:text-base peer-placeholder-shown:text-gray-400 peer-placeholder-shown:top-2 peer-focus:-top-3.5 peer-focus:text-sm ">Raumname
                        </label>
                        <jet-input-error :message="newRoomForm.error" class="mt-2"/>
                    </div>
                    <div class="mt-8 mr-4">
                                            <textarea
                                                placeholder="Kurzbeschreibung"
                                                v-model="newRoomForm.description" rows="4"
                                                class="focus:border-black placeholder-secondary border-2 w-full font-semibold border border-gray-300 "/>
                    </div>
                    <div class="flex items-center my-6">
                        <input v-model="newRoomForm.temporary"
                               type="checkbox"
                               class="ring-offset-0 cursor-pointer focus:ring-0 focus:shadow-none h-6 w-6 text-success border-2 border-gray-300"/>
                        <p :class="[newRoomForm.temporary ? 'text-primary font-black' : 'text-secondary']"
                           class="ml-4 my-auto text-sm">Temporärer Raum</p>
                        <div v-if="$page.props.can.show_hints" class="flex mt-1">
                            <SvgCollection svgName="arrowLeft" class="ml-2 mr-1 mt-1"/>
                            <span
                                class="font-nanum text-secondary tracking-tight ml-1 my-auto tracking-tight text-xl">Richte einen temporären Raum ein - z.B wenn ein Teil eines Raumes abgetrennt wird. Dieser wird nur in diesem Zeitraum im Kalender angezeigt.</span>
                        </div>
                    </div>
                    <div class="flex" v-if="newRoomForm.temporary">
                        <input
                            v-model="newRoomForm.start_date" id="startDate"
                            placeholder="Zu erledigen bis?" type="date"
                            class="border-gray-300 placeholder-secondary mr-2 w-full"/>
                        <input
                            v-model="newRoomForm.end_date" id="endDate"
                            placeholder="Zu erledigen bis?" type="date"
                            class="border-gray-300 placeholder-secondary w-full"/>
                    </div>

                    <button :class="[newRoomForm.name.length === 0 ?
                    'bg-secondary': 'bg-primary hover:bg-primaryHover focus:outline-none']"
                            class="mt-4 flex items-center px-20 py-3 border border-transparent
                            text-base font-bold uppercase shadow-sm text-secondaryHover"
                            @click="addRoom"
                            :disabled="newRoomForm.name.length === 0">
                        Anlegen
                    </button>
                </div>
            </div>
        </template>
    </jet-dialog-modal>
</template>

<script>

import AppLayout from '@/Layouts/AppLayout.vue'
import SvgCollection from "@/Layouts/Components/SvgCollection";
import Button from "@/Jetstream/Button";
import {
    ChevronDownIcon,
    DotsVerticalIcon,
    InformationCircleIcon,
    PencilAltIcon,
    SearchIcon, TrashIcon,
    XIcon, DuplicateIcon
} from "@heroicons/vue/outline";
import {CheckIcon, ChevronUpIcon, PlusSmIcon, XCircleIcon} from "@heroicons/vue/solid";
import {Menu, MenuButton, MenuItem, MenuItems} from "@headlessui/vue";
import JetButton from "@/Jetstream/Button";
import {defineComponent} from 'vue'
import JetDialogModal from "@/Jetstream/DialogModal";
import JetInput from "@/Jetstream/Input";
import JetInputError from "@/Jetstream/InputError";
import JetSecondaryButton from "@/Jetstream/SecondaryButton";
import {Link, useForm} from "@inertiajs/inertia-vue3";
import draggable from "vuedraggable";

export default defineComponent({
    components: {
        SvgCollection,
        Button,
        AppLayout,
        DotsVerticalIcon,
        PlusSmIcon,
        SearchIcon,
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
        XIcon,
        PencilAltIcon,
        TrashIcon,
        XCircleIcon,
        Link,
        DuplicateIcon,
        draggable
    },
    name: 'Area Management',
    props: ['areas'],
    data() {
        return {
            dragging: false,
            showMenu: null,
            showAddAreaModal: false,
            showAddRoomModal: false,
            newAreaForm: useForm({
                name: ''
            }),
            newRoomForm: useForm({
                name: '',
                description: '',
                temporary: false,
                start_date: null,
                end_date: null,
                area_id: null,
                user_id: this.$page.props.user.id,
            })
        }


    },
    methods: {
        openAddAreaModal() {
            this.showAddAreaModal = true;
        },
        closeAddAreaModal() {
            this.showAddAreaModal = false;
        },
        addArea() {
            this.newAreaForm.post(route('areas.store'), {});
            this.closeAddAreaModal();
        },
        addRoom() {
            this.newRoomForm.post(route('rooms.store'), {});
            this.closeAddRoomModal();
        },
        openAddRoomModal(area) {
            this.newRoomForm.area_id = area.id;
            this.showAddRoomModal = true;
        },
        closeAddRoomModal() {
            this.showAddRoomModal = false;
            this.newRoomForm.area_id = null;
            this.newRoomForm.name = "";
            this.newRoomForm.description = "";
            this.newRoomForm.start_date = null;
            this.newRoomForm.end_date = null;
        },
    },
})
</script>

<style scoped>

</style>
