<template>
    <div>
        <!-- Event Header -->
        <div class="w-full h-12 flex items-center justify-between px-4 text-white text-sm"
             :class="event.event_type.svg_name">
            <div>
                {{ event.event?.start_time }} | {{ event.event_type.abbreviation }} | {{ event.room.name }}

            </div>
            <div class="mt-1">
                <Menu as="div">
                    <div>
                        <MenuButton>
                            <DotsVerticalIcon
                                class="ml-2 -mr-1 h-5 w-5 text-white"
                            />
                        </MenuButton>
                    </div>

                    <transition
                        enter-active-class="transition duration-100 ease-out"
                        enter-from-class="transform scale-95 opacity-0"
                        enter-to-class="transform scale-100 opacity-100"
                        leave-active-class="transition duration-75 ease-in"
                        leave-from-class="transform scale-100 opacity-100"
                        leave-to-class="transform scale-95 opacity-0"
                    >
                        <MenuItems
                            class="p-5 absolute right-10 mt-2 w-64 origin-top-right rounded-md bg-primary shadow-lg ring-1 ring-black ring-opacity-5 focus:outline-none"
                        >
                            <XIcon class="ml-auto w-5 h-5 text-secondary"/>
                            <div
                                class="text-secondary mt-4 text-sm cursor-pointer"
                                @click="openDeletConfirmModal"
                            >
                                Schichtplanung löschen
                            </div>
                            <div class="text-secondary mt-4 text-sm">Schichtplanung als Vorlage speichern</div>
                            <div class="text-secondary mt-4 text-sm">Schichtplanung aus Vorlage einlesen</div>

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

        <div class="flex justify-start mt-3 overflow-x-scroll gap-3 h-full">
            <TimeLineShiftsComponent :time-line="event.timeline" :shifts="event.shifts" :crafts="crafts"
                                     :event="event.event"/>
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

export default defineComponent({
    name: "SingleRelevantEvent",
    props: ['event', 'crafts'],
    components: {
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
        MenuItems
    },
    data() {
        return {
            showConfirmDeleteModal: false
        }
    },
    methods: {
        openDeletConfirmModal() {
            this.showConfirmDeleteModal = true
        },
        closeConfirmDeleteModal() {
            this.showConfirmDeleteModal = false
        },
        deleteShift() {
            this.$inertia.delete(`/events/${this.event.event.id}/shifts`)
            this.showConfirmDeleteModal = false
        }
    }
})
</script>


<style scoped>

.eventType0 {
    background-color: #A7A6B115;
    stroke: #7F7E88;
    color: #7F7E88
}

.eventType1 {
    background-color: #641a5415;
    stroke: #631D53;
    color: #631D53
}

.eventType2 {
    background-color: #da3f8715;
    stroke: #D84387;
    color: #D84387
}

.eventType3 {
    background-color: #eb7a3d15;
    stroke: #E97A45;
    color: #E97A45
}

.eventType4 {
    background-color: #f1b64015;
    stroke: #CB8913;
    color: #CB8913
}

.eventType5 {
    background-color: #86c55415;
    stroke: #648928;
    color: #648928
}

.eventType6 {
    background-color: #2eaa6315;
    stroke: #35A965;
    color: #35A965
}

.eventType7 {
    background-color: #3dc3cb15;
    stroke: #35ACB2;
    color: #35ACB2
}

.eventType8 {
    background-color: #168fc315;
    stroke: #2290C1;
    color: #2290C1
}

.eventType9 {
    background-color: #4d908e15;
    stroke: #50908E;
    color: #50908E
}

.eventType10 {
    background-color: #21485C15;
    stroke: #23485B;
    color: #23485B
}
</style>
