<template>

    <div class="mb-3">
        <!-- Event Header -->
        <div class="w-full h-12 flex items-center justify-between px-4 text-white text-sm"
             :class="event.event_type.svg_name">
            <div class="flex items-center">
                {{ event.event?.start_time }} | {{ event.event_type.abbreviation }} | {{ event.room?.name }}

                <span v-if="event.event.is_series" class="ml-3">
                    <SvgCollection svg-name="iconRepeat"/>
                </span>
                <div class="ml-4 cursor-pointer" @click="showShift = !showShift">
                    <ChevronDownIcon class="h-4 w-4" v-if="!showShift"/>
                    <ChevronUpIcon class="h-4 w-4" v-else/>
                </div>
            </div>
            <div class="mt-1">
                <Menu as="div" class="relative">
                    <div class="flex p-0.5 rounded-full">
                        <MenuButton
                            class="flex p-0.5 rounded-full">
                            <DotsVerticalIcon
                                class=" flex-shrink-0 h-4 w-4 my-auto"
                                aria-hidden="true"/>
                        </MenuButton>

                    </div>
                    <transition enter-active-class="transition ease-out duration-100"
                                enter-from-class="transform opacity-0 scale-95"
                                enter-to-class="transform opacity-100 scale-100"
                                leave-active-class="transition ease-in duration-75"
                                leave-from-class="transform opacity-100 scale-100"
                                leave-to-class="transform opacity-0 scale-95">
                        <MenuItems
                            class="origin-top-right z-100 absolute right-0 mr-4 mt-2 w-80 shadow-lg bg-zinc-800 ring-1 ring-black ring-opacity-5 divide-y divide-gray-100 focus:outline-none">
                            <div class="py-1">
                                <MenuItem v-slot="{ active }">
                                    <a href="#" @click="openDeletConfirmModal"
                                       :class="[active ? 'bg-primaryHover text-white' : 'text-secondary', 'group flex items-center px-4 py-2 text-sm subpixel-antialiased']">
                                        <img src="/Svgs/IconSvgs/icon_menu_item.svg" class="w-5 h-5 mr-3"  alt="">
                                        Schichtplanung löschen
                                    </a>
                                </MenuItem>
                                <MenuItem v-slot="{ active }">
                                    <a href="#" @click="saveShiftAsPreset"
                                       :class="[active ? 'bg-primaryHover text-white' : 'text-secondary', 'group flex items-center px-4 py-2 text-sm subpixel-antialiased']">
                                        <img src="/Svgs/IconSvgs/icon_menu_item.svg" class="w-5 h-5 mr-3"  alt="">
                                        Schichtplanung als Vorlage speichern
                                    </a>
                                </MenuItem>
                                <MenuItem v-slot="{ active }">
                                    <a href="#" @click="showImportShiftTemplateModal = true"
                                       :class="[active ? 'bg-primaryHover text-white' : 'text-secondary', 'group flex items-center px-4 py-2 text-sm subpixel-antialiased']">
                                        <img src="/Svgs/IconSvgs/icon_menu_item.svg" class="w-5 h-5 mr-3"  alt="">
                                        Schichtplanung aus Vorlage einlesen
                                    </a>
                                </MenuItem>
                            </div>
                        </MenuItems>
                    </transition>
                </Menu>
            </div>
        </div>

        <ConfirmDeleteModal
            v-if="showConfirmDeleteModal"
            title="Schichtplanung löschen"
            description="Möchten sie die Schichtplanung löschen?"
            @closed="closeConfirmDeleteModal"
            @delete="deleteShift"
        />

        <AddShiftPresetModal
            v-if="showAddShiftPresetModal"
            @closed="showAddShiftPresetModal = false"
            :event_types="eventTypes"
            :event_type_id="event.event_type.id"
            :event-id="event.event.id"
        />

        <ImportShiftTemplate
            v-if="showImportShiftTemplateModal"
            @closed="showImportShiftTemplateModal = false"
            :event_type="event.event_type"
            :eventId="event.event.id"
        />

        <div class="flex justify-start mt-3 overflow-x-scroll gap-3 h-full" v-if="showShift">
            <TimeLineShiftsComponent :time-line="event.timeline"
                                     :shifts="event.shifts"
                                     :crafts="crafts"
                                     :currentUserCrafts="currentUserCrafts"
                                     :event="event.event"
                                     :shift-qualifications="shiftQualifications"
                                     @dropFeedback="dropFeedback"/>
        </div>
    </div>



</template>

<script>
import {defineComponent} from 'vue'
import Timeline from "@/Pages/Projects/Components/Timeline.vue";
import SingleShift from "@/Pages/Projects/Components/TimeLineShiftsComponent.vue";
import {PlusCircleIcon, DotsVerticalIcon, XIcon} from "@heroicons/vue/outline";
import TimeLineShiftsComponent from "@/Pages/Projects/Components/TimeLineShiftsComponent.vue";
import {Menu, MenuButton, MenuItems, MenuItem} from '@headlessui/vue'
import ConfirmDeleteModal from "@/Layouts/Components/ConfirmDeleteModal.vue";
import AddShiftPresetModal from "@/Pages/Projects/Components/AddShiftPresetModal.vue";
import {ChevronDownIcon, ChevronUpIcon} from "@heroicons/vue/outline";
import ImportShiftTemplate from "@/Pages/Projects/Components/ImportShiftTemplate.vue";
import SvgCollection from "@/Layouts/Components/SvgCollection.vue";


export default defineComponent({
    name: "SingleRelevantEvent",
    props: [
        'event',
        'crafts',
        'eventTypes',
        'currentUserCrafts',
        'shiftQualifications'
    ],
    emits: ['dropFeedback'],
    components: {
        SvgCollection,
        ImportShiftTemplate,
        AddShiftPresetModal,
        ConfirmDeleteModal,
        XIcon,
        TimeLineShiftsComponent,
        PlusCircleIcon,
        SingleShift,
        Timeline,
        DotsVerticalIcon,
        Menu,
        MenuItem,
        MenuButton,
        MenuItems,
        ChevronUpIcon,
        ChevronDownIcon
    },
    data() {
        return {
            showConfirmDeleteModal: false,
            showAddShiftPresetModal: false,
            showShift: parseInt(this.$page.props?.urlParameters?.eventId) === parseInt(this.event.event.id) ? true : false,
            showImportShiftTemplateModal: false,
        }
    },
    methods: {
        dropFeedback(event) {
            this.$emit('dropFeedback', event)
        },
        openDeletConfirmModal() {
            this.showConfirmDeleteModal = true
        },
        closeConfirmDeleteModal() {
            this.showConfirmDeleteModal = false
        },
        deleteShift() {
            this.$inertia.delete(`/events/${this.event.event.id}/shifts`)
            this.showConfirmDeleteModal = false
        },
        saveShiftAsPreset(){
            this.showAddShiftPresetModal = true
        }
    }
})
</script>


<style scoped>
.eventType0 {
    background-color: #A7A6B1;
    stroke: #7F7E88;
}

.eventType1 {
    background-color: #641A54;
    stroke: #631D53;
}

.eventType2 {
    background-color: #DA3F87;
    stroke: #D84387;
}

.eventType3 {
    background-color: #EB7A3D;
    stroke: #E97A45;
}

.eventType4 {
    background-color: #F1B640;
    stroke: #CB8913;
}

.eventType5 {
    background-color: #86C554;
    stroke: #648928;
}

.eventType6 {
    background-color: #2EAA63;
    stroke: #35A965;
}

.eventType7 {
    background-color: #3DC3CB;
    stroke: #35ACB2;
}

.eventType8 {
    background-color: #168FC3;
    stroke: #2290C1;
}

.eventType9 {
    background-color: #4D908E;
    stroke: #50908E;
}

.eventType10 {
    background-color: #21485C;
    stroke: #23485B;
}
</style>
