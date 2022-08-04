<template>
    <app-layout title="Projekteinstellungen">
        <div class="max-w-screen-lg my-8 ml-20 mr-40">
            <div class="">
                <h2 class="font-bold font-lexend text-3xl my-2">Termineinstellungen</h2>
                <div class="text-secondary tracking-tight leading-6 sub">
                    Definiere globale Einstellungen für Termine.
                </div>
            </div>
            <div class="mt-16 max-w-2xl">
                <div class="flex">
                    <h2 class="font-bold font-lexend text-xl my-2">Termintypen</h2>
                    <button @click="openAddEventTypeModal" type="button"
                            class="flex my-auto ml-6 items-center border border-transparent rounded-full shadow-sm text-white bg-primary hover:bg-primaryHover focus:outline-none">
                        <PlusSmIcon class="h-5 w-5" aria-hidden="true"/>
                    </button>
                    <div v-if="$page.props.can.show_hints" class="flex mt-1">
                        <SvgCollection svgName="arrowLeft" class="mt-1 ml-2"/>
                        <span class="font-nanum tracking-tight text-lg text-secondary ml-1 my-auto">Erstelle neue Termintypen</span>
                    </div>
                </div>
                <div class="text-secondary tracking-tight leading-6 sub">
                    Lege bis zu 10 Termintypen fest, denen Termine später zugeordnet werden können. Du kannst außerdem
                    definieren ob sie Projekten zugeordnet werden müssen oder ob sie einen eigenen individuellen
                    Terminnamen bekommen können.
                </div>
            </div>
            <ul role="list" class="mt-5 mb-20 w-full">
                <li v-for="(eventType,index) in event_types.data" :key="eventType.id"
                    class="flex justify-between">
                    <div class="flex my-4">
                        <EventTypeIconCollection :height="64" :width="64"
                                                 :iconName="eventType.svg_name"/>
                        <div class="ml-5 my-auto w-full justify-start mr-6">
                            <div class="flex my-auto">
                                <p class="text-lg subpixel-antialiased text-gray-900">{{ eventType.name }}</p>
                            </div>
                            <div class="flex mt-2">
                                <div class="text-secondary subpixel-antialiased mr-2">
                                    {{
                                        eventType.project_mandatory ? "Projektzuordnung verpflichtend" : "Projektzuordnung optional"
                                    }}
                                </div>
                                <div class="text-secondary subpixel-antialiased">
                                    |
                                </div>
                                <div class="text-secondary subpixel-antialiased ml-2">
                                    {{
                                        eventType.individual_name ? "individueller Terminname möglich" : "kein individueller Terminname möglich"
                                    }}
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="flex">
                        <Menu as="div" class="my-auto relative">
                            <div class="flex" v-if="index !== 0">
                                <MenuButton
                                    class="flex">
                                    <DotsVerticalIcon class="mr-3 flex-shrink-0 h-6 w-6 text-gray-600 my-auto"
                                                      aria-hidden="true"/>
                                </MenuButton>
                                <div v-if="$page.props.can.show_hints && index === 0" class="absolute flex w-40 ml-6">
                                    <div>
                                        <SvgCollection svgName="arrowLeft" class="mt-1 ml-2"/>
                                    </div>
                                    <div class="flex" v-if="index === 1">
                                        <span
                                            class="font-nanum ml-2 text-secondary tracking-tight tracking-tight text-lg">Bearbeite einen Termintypen</span>
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
                                            <a href="#" @click="openEditEventTypeModal(eventType)"
                                               :class="[active ? 'bg-primaryHover text-white' : 'text-secondary', 'group flex items-center px-4 py-2 text-sm subpixel-antialiased']">
                                                <PencilAltIcon
                                                    class="mr-3 h-5 w-5 text-primaryText group-hover:text-white"
                                                    aria-hidden="true"/>
                                                Termintyp bearbeiten
                                            </a>
                                        </MenuItem>
                                        <MenuItem v-slot="{ active }">
                                            <a href="#" @click="openDeleteEventTypeModal(eventType)"
                                               :class="[active ? 'bg-primaryHover text-white' : 'text-secondary', 'group flex items-center px-4 py-2 text-sm subpixel-antialiased']">
                                                <TrashIcon
                                                    class="mr-3 h-5 w-5 text-primaryText group-hover:text-white"
                                                    aria-hidden="true"/>
                                                Termintyp entfernen
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
        <!-- Termintyp erstellen Modal-->
        <jet-dialog-modal :show="addingEventType" @close="closeAddEventTypeModal">
            <template #content>
                <div class="mx-4">
                    <div class="font-bold font-lexend text-primary tracking-wide text-2xl my-2">
                        Neuen Termintyp erstellen
                    </div>
                    <XIcon @click="closeAddEventTypeModal"
                           class="h-5 w-5 right-0 top-0 mt-8 mr-5 absolute cursor-pointer"
                           aria-hidden="true"/>
                    <div class="text-secondary subpixel-antialiased">
                        Du kannst bis zu 10 Termintypen festlegen
                    </div>
                    <div class="mt-4">
                        <div class="flex">
                            <Menu as="div" class=" relative">
                                <div>
                                    <MenuButton class="flex items-center rounded-full focus:outline-none">
                                        <ChevronDownIcon v-if="eventTypeForm.svg_name === ''"
                                                         class="ml-1 flex-shrink-0 mt-1 h-16 w-16 flex my-auto items-center rounded-full shadow-sm text-white bg-black"></ChevronDownIcon>
                                        <EventTypeIconCollection v-else :height="64" :width="64"
                                                                 :iconName="eventTypeForm.svg_name"/>
                                    </MenuButton>
                                </div>
                                <transition enter-active-class="transition ease-out duration-100"
                                            enter-from-class="transform opacity-0 scale-95"
                                            enter-to-class="transform opacity-100 scale-100"
                                            leave-active-class="transition ease-in duration-75"
                                            leave-from-class="transform opacity-100 scale-100"
                                            leave-to-class="transform opacity-0 scale-95">
                                    <MenuItems
                                        class="z-40 origin-top-right h-40 w-24 absolute right-0 mt-2 shadow-lg py-1 bg-primary ring-1 ring-black ring-opacity-5 focus:outline-none overflow-y-auto">
                                        <MenuItem v-for="item in iconMenuItems" v-slot="{ active }">
                                            <div v-if="item.taken === false">
                                                <div class="" @click="eventTypeForm.svg_name = item.iconName"
                                                     :class="[active ? 'bg-primaryHover text-secondaryHover' : 'text-secondary', 'group relative flex  items-center px-4 py-2 text-sm subpixel-antialiased']">
                                                    <EventTypeIconCollection :height="64" :width="64"
                                                                             :iconName="item.iconName"/>
                                                </div>
                                            </div>
                                            <div v-else>
                                            </div>
                                        </MenuItem>
                                    </MenuItems>
                                </transition>
                            </Menu>
                            <div class="relative my-auto w-full ml-8 mr-12">
                                <input id="name" v-model="eventTypeForm.name" type="text"
                                       class="peer pl-0 h-12 w-full focus:border-t-transparent focus:border-primary focus:ring-0 border-l-0 border-t-0 border-r-0 border-b-2 border-gray-300 text-primary placeholder-secondary placeholder-transparent"
                                       placeholder="placeholder"/>
                                <label for="name"
                                       class="absolute left-0 text-base -top-5 text-gray-600 text-sm -top-3.5 transition-all subpixel-antialiased focus:outline-none text-secondary peer-placeholder-shown:text-base peer-placeholder-shown:text-gray-400 peer-placeholder-shown:top-2 peer-focus:-top-3.5 peer-focus:text-sm ">Name
                                    des Termintyps</label>
                            </div>
                        </div>
                        <div>
                            <div class="flex items-center mt-8 ml-5">
                                <input v-model="eventTypeForm.project_mandatory"
                                       type="checkbox"
                                       class="ring-offset-0 cursor-pointer focus:ring-0 focus:shadow-none h-6 w-6 text-success border-2 border-gray-300"/>
                                <p :class="[eventTypeForm.project_mandatory ? 'text-primary font-black' : 'text-secondary']"
                                   class="ml-4 my-auto text-sm">Projektzuordnung verpflichtend</p>
                            </div>
                            <div class="flex items-center mt-4 ml-5">
                                <input v-model="eventTypeForm.individual_name"
                                       type="checkbox"
                                       class="ring-offset-0 cursor-pointer focus:ring-0 focus:shadow-none h-6 w-6 text-success border-2 border-gray-300"/>
                                <p :class="[eventTypeForm.individual_name ? 'text-primary font-black' : 'text-secondary']"
                                   class="ml-4 my-auto text-sm">individueller Terminname möglich</p>
                            </div>
                        </div>
                        <div class="mt-2 ml-5">
                            <button
                                :class="[this.eventTypeForm.name === '' || this.eventTypeForm.svg_name === '' ? 'bg-secondary': 'bg-primary hover:bg-primaryHover focus:outline-none']"
                                class="mt-8 inline-flex items-center px-20 py-3 border focus:outline-none border-transparent text-base font-bold text-xl uppercase shadow-sm text-secondaryHover"
                                @click="addEventType"
                                :disabled="this.eventTypeForm.name === '' || this.eventTypeForm.svg_name === ''">
                                Speichern
                            </button>
                        </div>
                    </div>
                </div>
            </template>
        </jet-dialog-modal>
        <!-- Termintyp bearbeiten Modal-->
        <jet-dialog-modal :show="editingEventType" @close="closeEditEventTypeModal">
            <template #content>
                <div class="mx-4">
                    <div class="font-bold font-lexend text-primary tracking-wide text-2xl my-2">
                        Termintyp bearbeiten
                    </div>
                    <XIcon @click="closeEditEventTypeModal"
                           class="h-5 w-5 right-0 top-0 mt-8 mr-5 absolute cursor-pointer"
                           aria-hidden="true"/>
                    <div class="mt-12">
                        <div class="flex">
                            <Menu as="div" class=" relative">
                                <div>
                                    <MenuButton class="flex items-center rounded-full focus:outline-none">
                                        <ChevronDownIcon v-if="editEventTypeForm.svg_name === ''"
                                                         class="ml-1 flex-shrink-0 mt-1 h-16 w-16 flex my-auto items-center rounded-full shadow-sm text-white bg-black"></ChevronDownIcon>
                                        <EventTypeIconCollection :height="64" :width="64" v-else
                                                                 :iconName="editEventTypeForm.svg_name"/>
                                    </MenuButton>
                                </div>
                                <transition enter-active-class="transition ease-out duration-100"
                                            enter-from-class="transform opacity-0 scale-95"
                                            enter-to-class="transform opacity-100 scale-100"
                                            leave-active-class="transition ease-in duration-75"
                                            leave-from-class="transform opacity-100 scale-100"
                                            leave-to-class="transform opacity-0 scale-95">
                                    <MenuItems
                                        class="z-40 origin-top-right h-40 w-24 absolute right-0 mt-2 shadow-lg py-1 bg-primary ring-1 ring-black ring-opacity-5 focus:outline-none overflow-y-auto">
                                        <MenuItem v-for="item in iconMenuItems" v-slot="{ active }">
                                            <div v-if="item.taken === false">
                                                <Link href="#" @click="editEventTypeForm.svg_name = item.iconName"
                                                      :class="[active ? 'bg-primaryHover text-secondaryHover' : 'text-secondary', 'group flex items-center px-4 py-2 text-sm subpixel-antialiased']">
                                                    <EventTypeIconCollection :height="64" :width="64"
                                                                             :iconName="item.iconName"/>
                                                </Link>
                                            </div>
                                            <div v-else class="text-secondary">
                                                {{ item.iconName }} schon vergeben
                                            </div>
                                        </MenuItem>
                                    </MenuItems>
                                </transition>
                            </Menu>
                            <div class="relative my-auto w-full ml-8 mr-12">
                                <input id="editCategoryName" v-model="editEventTypeForm.name" type="text"
                                       class="peer pl-0 h-12 w-full focus:border-t-transparent focus:border-primary focus:ring-0 border-l-0 border-t-0 border-r-0 border-b-2 border-gray-300 text-primary placeholder-secondary placeholder-transparent"
                                       placeholder="placeholder"/>
                                <label for="editCategoryName"
                                       class="absolute left-0 text-base -top-5 text-gray-600 text-sm -top-3.5 transition-all subpixel-antialiased focus:outline-none text-secondary peer-placeholder-shown:text-base peer-placeholder-shown:text-gray-400 peer-placeholder-shown:top-2 peer-focus:-top-3.5 peer-focus:text-sm ">Name</label>
                            </div>
                        </div>
                        <div>
                            <div class="flex items-center mt-8 ml-5">
                                <input v-model="editEventTypeForm.project_mandatory"
                                       type="checkbox"
                                       class="ring-offset-0 cursor-pointer focus:ring-0 focus:shadow-none h-6 w-6 text-success border-2 border-gray-300"/>
                                <p :class="[editEventTypeForm.project_mandatory ? 'text-primary font-black' : 'text-secondary']"
                                   class="ml-4 my-auto text-sm">Projektzuordnung verpflichtend</p>
                            </div>
                            <div class="flex items-center mt-4 ml-5">
                                <input v-model="editEventTypeForm.individual_name"
                                       type="checkbox"
                                       class="ring-offset-0 cursor-pointer focus:ring-0 focus:shadow-none h-6 w-6 text-success border-2 border-gray-300"/>
                                <p :class="[editEventTypeForm.individual_name ? 'text-primary font-black' : 'text-secondary']"
                                   class="ml-4 my-auto text-sm">individueller Terminname möglich</p>
                            </div>
                        </div>
                        <div class="mt-2 ml-5">
                            <button
                                :class="[this.editEventTypeForm.name === '' || this.editEventTypeForm.svg_name === '' ? 'bg-secondary': 'bg-primary hover:bg-primaryHover focus:outline-none']"
                                class="mt-8 inline-flex items-center px-20 py-3 border focus:outline-none border-transparent text-base font-bold text-xl uppercase shadow-sm text-secondaryHover"
                                @click="editEventType"
                                :disabled="this.editEventTypeForm.name === '' || this.editEventTypeForm.svg_name === ''">
                                Speichern
                            </button>
                        </div>
                    </div>
                </div>
            </template>
        </jet-dialog-modal>
        <!-- Termintyp löschen Modal -->
        <jet-dialog-modal :show="deletingEventType" @close="closeDeleteEventTypeModal">
            <template #content>
                <div class="mx-4">
                    <div class="font-bold text-primary text-2xl my-2">
                        Termintyp löschen
                    </div>
                    <XIcon @click="closeDeleteEventTypeModal"
                           class="h-5 w-5 right-0 top-0 mr-5 mt-8 flex text-secondary absolute cursor-pointer"
                           aria-hidden="true"/>
                    <div class="text-error">
                        Bist du sicher, dass du den Termintyp {{ eventTypeToDelete.name }} löschen möchtest?
                        Alle Termine, die diesem Typen zugeordnet sind, werden auf "undefiniert" gesetzt.
                    </div>
                    <div class="flex justify-between mt-6">
                        <button class="bg-primary focus:outline-none my-auto inline-flex items-center px-20 py-3 border border-transparent
                            text-base font-bold uppercase shadow-sm text-secondaryHover"
                                @click="deleteEventType">
                            Löschen
                        </button>
                        <div class="flex my-auto">
                            <span @click="closeDeleteEventTypeModal"
                                  class="text-secondary subpixel-antialiased cursor-pointer">Nein, doch nicht</span>
                        </div>
                    </div>
                </div>
            </template>
        </jet-dialog-modal>
        <jet-dialog-modal :show="deletingUndefined" @close="closeDeletingUndefined">
            <template #content>
                <div class="mx-4">
                    <div class="font-bold text-primary text-2xl my-2">
                        Termintyp löschen
                    </div>
                    <XIcon @click="closeDeletingUndefined"
                           class="h-5 w-5 right-0 top-0 mr-5 mt-8 flex text-secondary absolute cursor-pointer"
                           aria-hidden="true"/>
                    <div class="text-error">
                        Der Termintyp {{ eventTypeToDelete.name }} kann nicht gelöscht werden, da er der
                        Standard-Termintyp ist.
                    </div>
                    <div class="flex justify-between mt-6">
                        <div class="flex my-auto">
                            <span @click="closeDeletingUndefined"
                                  class="text-secondary subpixel-antialiased cursor-pointer">Ok</span>
                        </div>
                    </div>
                </div>
            </template>
        </jet-dialog-modal>
    </app-layout>
</template>

<script>
import AppLayout from '@/Layouts/AppLayout.vue';
import {DotsVerticalIcon, TrashIcon, PencilAltIcon, XIcon} from "@heroicons/vue/outline"
import {CheckIcon, ChevronDownIcon, PlusSmIcon, XCircleIcon} from "@heroicons/vue/solid";
import SvgCollection from "@/Layouts/Components/SvgCollection";
import {Menu, MenuButton, MenuItem, MenuItems} from "@headlessui/vue";
import JetDialogModal from "@/Jetstream/DialogModal";
import EventTypeIconCollection from "@/Layouts/Components/EventTypeIconCollection";

export default {
    computed: {
        iconMenuItems() {
            this.event_types.data.forEach((eventType) => {
                if (this.takenEventTypeColors.includes(eventType.svg_name)) {
                    //do nothing
                } else {
                    this.takenEventTypeColors.push(eventType.svg_name)
                }
            })
            return [
                {
                    iconName: 'orange',
                    taken: this.takenEventTypeColors.includes('orange'),
                },
                {
                    iconName: 'turquoise',
                    taken: this.takenEventTypeColors.includes('turquoise'),
                },
                {
                    iconName: 'green',
                    taken: this.takenEventTypeColors.includes('green'),
                },
            ]
        }
    },
    components: {
        AppLayout,
        XCircleIcon,
        PlusSmIcon,
        SvgCollection,
        CheckIcon,
        Menu,
        MenuButton,
        MenuItem,
        MenuItems,
        JetDialogModal,
        EventTypeIconCollection,
        ChevronDownIcon,
        DotsVerticalIcon,
        TrashIcon,
        PencilAltIcon,
        XIcon
    },
    props: ['event_types'],
    data() {
        return {
            addingEventType: false,
            editingEventType: false,
            deletingEventType: false,
            deletingUndefined: false,
            eventTypeToDelete: null,
            takenEventTypeColors: [],
            eventTypeForm: this.$inertia.form({
                _method: 'POST',
                svg_name: '',
                name: '',
                project_mandatory: false,
                individual_name: false,
            }),
            editEventTypeForm: this.$inertia.form({
                _method: 'PATCH',
                svg_name: '',
                name: '',
                project_mandatory: false,
                individual_name: false,
                id: null
            })
        }
    },
    methods: {
        openAddEventTypeModal() {
            this.addingEventType = true;
        },
        openEditEventTypeModal(eventType) {
            this.editEventTypeForm.svg_name = eventType.svg_name;
            this.editEventTypeForm.name = eventType.name;
            this.editEventTypeForm.id = eventType.id;
            this.editEventTypeForm.project_mandatory = eventType.project_mandatory;
            this.editEventTypeForm.individual_name = eventType.individual_name;
            this.editingEventType = true;
        },
        closeEditEventTypeModal() {
            this.editEventTypeForm.svg_name = "";
            this.editEventTypeForm.name = "";
            this.editEventTypeForm.id = null;
            this.editEventTypeForm.project_mandatory = false;
            this.editEventTypeForm.individual_name = false;
            this.editingEventType = false;
        },
        closeAddEventTypeModal() {
            this.addingEventType = false;
            this.eventTypeForm.name = "";
            this.eventTypeForm.svg_name = "";
            this.eventTypeForm.project_mandatory = false;
            this.eventTypeForm.individual_name = false;
        },
        addEventType() {
            this.eventTypeForm.post(route('event_types.store', {event_type: this.eventTypeForm}));
            this.closeAddEventTypeModal();
        },
        editEventType() {
            this.editEventTypeForm.patch(route('event_types.update', {event_type: this.editEventTypeForm.id}));
            this.closeEditEventTypeModal();
        },
        openDeleteEventTypeModal(eventType) {
            this.eventTypeToDelete = eventType;
            if (this.eventTypeToDelete.id === 1) {
                this.deletingUndefined = true;
            }
            this.deletingEventType = true;
        },
        closeDeletingUndefined() {
            this.deletingUndefined = false,
                this.eventTypeToDelete = null
        },
        closeDeleteEventTypeModal() {
            this.deletingEventType = false;
            this.eventTypeToDelete = null;
        },
        deleteEventType() {
            this.$inertia.delete(`../event_types/${this.eventTypeToDelete.id}`);
            this.closeDeleteEventTypeModal();
        }
    },
    setup() {
        return {}
    }
}
</script>
