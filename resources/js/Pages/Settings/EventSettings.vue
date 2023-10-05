<template>
    <app-layout>
        <div class="max-w-screen-lg ml-14 mr-40">
            <div class="">
                <h2 class="headline1">Termineinstellungen</h2>
                <div class="xsLight mt-2">
                    Definiere globale Einstellungen für Termine.
                </div>
            </div>
            <div class="mt-4 max-w-2xl">
                <div class="flex">
                    <h2 class="headline2 my-2">Termintypen</h2>
                    <AddButton @click="openAddEventTypeModal" text="Neuer Termintyp" mode="page"/>
                    <div v-if="$page.props.can.show_hints" class="flex mt-1">
                        <SvgCollection svgName="arrowLeft" class="mt-1 ml-2"/>
                        <span class="hind ml-1 my-auto">Erstelle neue Termintypen</span>
                    </div>
                </div>
                <div class="xsLight mt-2">
                    Lege bis zu 10 Termintypen fest, denen Termine später zugeordnet werden können. Du kannst außerdem
                    definieren ob sie Projekten zugeordnet werden müssen oder ob sie einen eigenen individuellen
                    Terminnamen bekommen können.
                </div>
            </div>
            <ul role="list" class="mt-4 mb-20 w-full">
                <li v-for="(eventType,index) in event_types" :key="eventType.id"
                    class="flex justify-between">
                    <div class="flex my-4">
                        <EventTypeIconCollection :height="64" :width="64"
                                                 :iconName="eventType.svg_name"/>
                        <div class="ml-5 my-auto w-full justify-start mr-6">
                            <div class="flex my-auto">
                                <p class="mDark">{{ eventType.name }}</p>
                                <div class="ml-2 mDark">({{eventType.abbreviation}})</div>
                            </div>
                            <div class="flex mt-2">
                                <div class="xsLight mr-2">
                                    {{
                                        eventType.project_mandatory ? "Projektzuordnung verpflichtend" : "Projektzuordnung optional"
                                    }}
                                </div>
                                <div class="xsLight">
                                    |
                                </div>
                                <div class="xsLight ml-2">
                                    {{
                                        eventType.individual_name ? "individueller Terminname verpflichtend" : "kein individueller Terminname verpflichtend"
                                    }}
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="flex">
                        <Menu as="div" class="my-auto relative">
                            <div class="flex" v-if="index !== 0">
                                <MenuButton
                                    class="flex bg-tagBg p-0.5 rounded-full">
                                    <DotsVerticalIcon
                                        class=" flex-shrink-0 h-6 w-6 text-menuButtonBlue my-auto"
                                        aria-hidden="true"/>
                                </MenuButton>
                                <div v-if="$page.props.can.show_hints && index === 0" class="absolute flex w-40 ml-6">
                                    <div>
                                        <SvgCollection svgName="arrowLeft" class="mt-1 ml-2"/>
                                    </div>
                                    <div class="flex" v-if="index === 1">
                                        <span
                                            class="hind ml-2 text-secondary tracking-tight tracking-tight text-lg">Bearbeite einen Termintypen</span>
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
                                    class="z-10 origin-top-right absolute right-0 mr-4 mt-2 w-72 shadow-lg bg-primary ring-1 ring-black ring-opacity-5 divide-y divide-gray-100 focus:outline-none">
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
                <img src="/Svgs/Overlays/illu_appointment_new.svg" class="-ml-6 -mt-8 mb-4" />
                <div class="mx-4">
                    <div class="headline1 my-2">
                        Neuer Termintyp
                    </div>
                    <XIcon @click="closeAddEventTypeModal"
                           class="h-5 w-5 right-0 top-0 mt-8 mr-5 absolute cursor-pointer"
                           aria-hidden="true"/>
                    <div class="xsLight">
                        Du kannst bis zu 10 Termintypen festlegen.
                    </div>
                    <div class="mt-4">
                        <div class="flex">
                            <Menu as="div" class=" relative">
                                <div>
                                    <MenuButton :class="[eventTypeForm.svg_name === '' ? 'border border-gray-400' : '']" class="items-center rounded-full focus:outline-none h-14 w-14">
                                        <label v-if="eventTypeForm.svg_name === ''" class="text-gray-400 text-xs">Farbe*</label>
                                        <ChevronDownIcon v-if="eventTypeForm.svg_name === ''"
                                                         class="h-4 w-4 mx-auto items-center rounded-full shadow-sm text-black"></ChevronDownIcon>
                                        <EventTypeIconCollection class="h-12 w-12" v-if="eventTypeForm.svg_name !== ''" :iconName=eventTypeForm.svg_name alt="TeamIcon" />
                                    </MenuButton>
                                </div>
                                <transition enter-active-class="transition ease-out duration-100"
                                            enter-from-class="transform opacity-0 scale-95"
                                            enter-to-class="transform opacity-100 scale-100"
                                            leave-active-class="transition ease-in duration-75"
                                            leave-from-class="transform opacity-100 scale-100"
                                            leave-to-class="transform opacity-0 scale-95">
                                    <MenuItems
                                        class="z-40 origin-top-right h-32 overflow-y-auto w-20 absolute right-0 mt-2 shadow-lg py-1 bg-primary ring-1 ring-black ring-opacity-5 focus:outline-none overflow-y-auto">
                                        <MenuItem v-for="item in iconMenuItems" v-slot="{ active }">
                                            <div>
                                                <div class="" @click="eventTypeForm.svg_name = item.iconName"
                                                     :class="[active ? 'bg-primaryHover text-secondaryHover' : 'text-secondary', 'group relative flex  items-center px-4 py-2 text-sm subpixel-antialiased']">
                                                    <EventTypeIconCollection :height="64" :width="64"
                                                                             :iconName="item.iconName"/>
                                                </div>
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
                                       class="absolute left-0 -top-5 text-gray-600 text-sm -top-3.5 transition-all subpixel-antialiased focus:outline-none text-secondary peer-placeholder-shown:text-base peer-placeholder-shown:text-gray-400 peer-placeholder-shown:top-2 peer-focus:-top-3.5 peer-focus:text-sm ">Name
                                    des Termintyps*</label>
                            </div>
                        </div>
                        <div class="mt-4">
                            <input placeholder="Abkürzung des Eventtyps"
                                   id="entry_fee"
                                   v-model="eventTypeForm.abbreviation"
                                   class="mt-4 p-4 inputMain resize-none w-full xsDark placeholder:xsLight placeholder:subpixel-antialiased focus:outline-none focus:ring-0 focus:border-secondary focus:border-1 w-full border-gray-300"/>
                        </div>
                        <div>
                            <div class="flex items-center mt-8">
                                <input v-model="eventTypeForm.project_mandatory"
                                       type="checkbox"
                                       class="ring-offset-0 cursor-pointer focus:ring-0 focus:shadow-none h-6 w-6 text-success border-2 border-gray-300"/>
                                <p :class="[eventTypeForm.project_mandatory ? 'xsDark' : 'xsLight']"
                                   class="ml-4 my-auto ">Projektzuordnung verpflichtend</p>
                            </div>
                            <div class="flex items-center mt-4">
                                <input v-model="eventTypeForm.individual_name"
                                       type="checkbox"
                                       class="ring-offset-0 cursor-pointer focus:ring-0 focus:shadow-none h-6 w-6 text-success border-2 border-gray-300"/>
                                <p :class="[eventTypeForm.individual_name ? 'xsDark' : 'xsLight']"
                                   class="ml-4 my-auto ">individueller Terminname verpflichtend</p>
                            </div>
                        </div>
                        <div class="mt-2 w-full items-center text-center">
                            <AddButton
                                :class="[this.eventTypeForm.name === '' || this.eventTypeForm.svg_name === '' ? 'bg-secondary': 'bg-buttonBlue hover:bg-buttonHover focus:outline-none']"
                                class="mt-8 inline-flex items-center px-20 py-3 border focus:outline-none border-transparent text-base font-bold text-xl shadow-sm text-secondaryHover"
                                @click="addEventType"
                                :disabled="this.eventTypeForm.name === '' || this.eventTypeForm.svg_name === ''" text="Termintyp anlegen" mode="modal" />
                        </div>
                    </div>
                </div>
            </template>
        </jet-dialog-modal>
        <!-- Termintyp bearbeiten Modal-->
        <jet-dialog-modal :show="editingEventType" @close="closeEditEventTypeModal">
            <template #content>
                <img src="/Svgs/Overlays/illu_appointment_edit.svg" class="-ml-6 -mt-8 mb-4" />
                <div class="mx-4">
                    <div class="headline1 my-2">
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
                                        class="z-40 origin-top-right h-32 overflow-y-auto w-20 absolute right-0 mt-2 shadow-lg py-1 bg-primary ring-1 ring-black ring-opacity-5 focus:outline-none">
                                        <MenuItem v-for="item in iconMenuItems" v-slot="{ active }">
                                            <div>
                                                <div class="" @click="editEventTypeForm.svg_name = item.iconName"
                                                     :class="[active ? 'bg-primaryHover text-secondaryHover' : 'text-secondary', 'group relative flex  items-center px-4 py-2 text-sm subpixel-antialiased']">
                                                    <EventTypeIconCollection :height="64" :width="64"
                                                                             :iconName="item.iconName"/>
                                                </div>kjhb
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
                        <div class="mt-4">
                            <input :placeholder="editEventTypeForm.abbreviation !== '' ? editEventTypeForm.abbreviation : 'Abkürzung des Eventtyps'"
                                   id="entry_fee"
                                   v-model="editEventTypeForm.abbreviation"
                                   class="mt-4 p-4 inputMain resize-none w-full xsDark placeholder:xsLight placeholder:subpixel-antialiased focus:outline-none focus:ring-0 focus:border-secondary focus:border-1 w-full border-gray-300"/>
                        </div>
                        <div>
                            <div class="flex items-center mt-8">
                                <input v-model="editEventTypeForm.project_mandatory"
                                       type="checkbox"
                                       class="ring-offset-0 cursor-pointer focus:ring-0 focus:shadow-none h-6 w-6 text-success border-2 border-gray-300"/>
                                <p :class="[editEventTypeForm.project_mandatory ? 'xsDark' : 'xsLight']"
                                   class="ml-4 my-auto">Projektzuordnung verpflichtend</p>
                            </div>
                            <div class="flex items-center mt-4">
                                <input v-model="editEventTypeForm.individual_name"
                                       type="checkbox"
                                       class="ring-offset-0 cursor-pointer focus:ring-0 focus:shadow-none h-6 w-6 text-success border-2 border-gray-300"/>
                                <p :class="[editEventTypeForm.individual_name ? 'xsDark' : 'xsLight']"
                                   class="ml-4 my-auto">individueller Terminname verpflichtend</p>
                            </div>
                        </div>
                        <div class="mt-8 w-full justify-center flex">
                            <AddButton
                                class="inline-flex items-center px-20 py-3 border focus:outline-none border-transparent text-base font-bold text-xl shadow-sm text-secondaryHover"
                                @click="editEventType"
                                :disabled="this.editEventTypeForm.name === '' || this.editEventTypeForm.svg_name === ''" text="Speichern" mode="modal" />
                        </div>
                    </div>
                </div>
            </template>
        </jet-dialog-modal>
        <!-- Termintyp löschen Modal -->
        <jet-dialog-modal :show="deletingEventType" @close="closeDeleteEventTypeModal">
            <template #content>
                <div class="mx-4">
                    <div class="headline1 my-2">
                        Termintyp löschen
                    </div>
                    <XIcon @click="closeDeleteEventTypeModal"
                           class="h-5 w-5 right-0 top-0 mr-5 mt-8 flex text-secondary absolute cursor-pointer"
                           aria-hidden="true"/>
                    <div class="errorText">
                        Bist du sicher, dass du den Termintyp {{ eventTypeToDelete.name }} löschen möchtest?
                        Alle Termine, die diesem Typen zugeordnet sind, werden auf "undefiniert" gesetzt.
                    </div>
                    <div class="flex justify-between mt-6">
                        <button class="bg-buttonBlue hover:bg-buttonHover focus:outline-none my-auto inline-flex items-center px-20 py-3 border border-transparent
                            text-base font-bold uppercase shadow-sm text-secondaryHover"
                                @click="deleteEventType">
                            Löschen
                        </button>
                        <div class="flex my-auto">
                            <span @click="closeDeleteEventTypeModal"
                                  class="xsLight cursor-pointer">Nein, doch nicht</span>
                        </div>
                    </div>
                </div>
            </template>
        </jet-dialog-modal>
        <jet-dialog-modal :show="deletingUndefined" @close="closeDeletingUndefined">
            <template #content>
                <div class="mx-4">
                    <div class="headline1">
                        Termintyp löschen
                    </div>
                    <XIcon @click="closeDeletingUndefined"
                           class="h-5 w-5 right-0 top-0 mr-5 mt-8 flex text-secondary absolute cursor-pointer"
                           aria-hidden="true"/>
                    <div class="errorText">
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
import AddButton from "@/Layouts/Components/AddButton";
import {DotsVerticalIcon, TrashIcon, PencilAltIcon, XIcon} from "@heroicons/vue/outline"
import {CheckIcon, ChevronDownIcon, PlusSmIcon, XCircleIcon} from "@heroicons/vue/solid";
import SvgCollection from "@/Layouts/Components/SvgCollection";
import {Menu, MenuButton, MenuItem, MenuItems} from "@headlessui/vue";
import JetDialogModal from "@/Jetstream/DialogModal";
import EventTypeIconCollection from "@/Layouts/Components/EventTypeIconCollection";
import Permissions from "@/mixins/Permissions.vue";

export default {
    mixins: [Permissions],
    computed: {
        iconMenuItems() {
            return [
                {
                    iconName: 'eventType1',
                },
                {
                    iconName: 'eventType2',
                },
                {
                    iconName: 'eventType3',
                },
                {
                    iconName: 'eventType4',
                },
                {
                    iconName: 'eventType5',
                },
                {
                    iconName: 'eventType6',
                },
                {
                    iconName: 'eventType7',
                },
                {
                    iconName: 'eventType8',
                },
                {
                    iconName: 'eventType9',
                },
                {
                    iconName: 'eventType10',
                },
            ]
        }
    },
    components: {
        AddButton,
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
                abbreviation: ''
            }),
            editEventTypeForm: this.$inertia.form({
                _method: 'PATCH',
                svg_name: '',
                name: '',
                project_mandatory: false,
                individual_name: false,
                abbreviation: '',
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
            this.editEventTypeForm.abbreviation = eventType.abbreviation;
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
            this.eventTypeForm.abbreviation = '';
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
