<template>
    <div class="mb-3 flex flex-col gap-2">
        <!-- Event Header -->
        <div class="w-full h-12 flex items-center justify-between px-4 text-white text-sm rounded-lg"
             :style="{backgroundColor: event.event_type.hex_code}">
            <div class="flex items-center">
                <span v-if="!event.event.allDay">
                    {{ event.event?.formatted_dates.start }} - {{ event.event?.formatted_dates.end }} | {{ event.event_type.abbreviation }} | {{ event.room?.name }}
                </span>

                <span v-else>
                    {{ event.event?.event_date_without_time.start }} - {{ event.event?.event_date_without_time.end }} {{ $t('All day') }} | {{ event.event_type.abbreviation }} | {{ event.room?.name }}
                </span>

                <span v-if="event.event.is_series" class="ml-3">
                    <IconRepeat class="h-4 w-4" />
                </span>
                <div class="ml-4 cursor-pointer" @click="showShift = !showShift">
                    <IconChevronDown class="h-4 w-4" v-if="!showShift"/>
                    <IconChevronUp class="h-4 w-4" v-else/>
                </div>
            </div>
            <div class="mt-1">
                <BaseMenu dots-size="h-4 w-4">
                    <MenuItem v-slot="{ active }">
                        <a href="#" @click="openDeleteConfirmModal"
                           :class="[active ? 'bg-artwork-navigation-color/10 text-white' : 'text-secondary', 'group flex items-center px-4 py-2 text-sm subpixel-antialiased']">
                            <IconTrash stroke-width="1.5" class="w-5 h-5 mr-3" />
                            {{ $t('Delete shift planning') }}
                        </a>
                    </MenuItem>
                    <MenuItem v-slot="{ active }">
                        <a href="#" @click="saveShiftAsPreset"
                           :class="[active ? 'bg-artwork-navigation-color/10 text-white' : 'text-secondary', 'group flex items-center px-4 py-2 text-sm subpixel-antialiased']">
                            <IconFilePlus stroke-width="1.5" class="w-5 h-5 mr-3" />
                            {{ $t('Save shift planning as a template') }}
                        </a>
                    </MenuItem>
                    <MenuItem v-slot="{ active }">
                        <a href="#" @click="showImportShiftTemplateModal = true"
                           :class="[active ? 'bg-artwork-navigation-color/10 text-white' : 'text-secondary', 'group flex items-center px-4 py-2 text-sm subpixel-antialiased']">
                            <IconFileImport stroke-width="1.5" class="w-5 h-5 mr-3" />
                            {{ $t('Import shift planning from template') }}
                        </a>
                    </MenuItem>
                </BaseMenu>
            </div>
        </div>
        <ConfirmDeleteModal
            v-if="showConfirmDeleteModal"
            :title="$t('Delete shift planning')"
            :description="$t('Would you like to delete the shift planning?')"
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
            @closed="this.closeImportShiftTemplateModal()"
            :event_type="event.event_type"
            :eventId="event.event.id"
        />
        <!-- Event Timeline -->
        <div class="" v-if="showShift">
            <TimeLineShiftsComponent ref="timelineShiftsComponent"
                                     :time-line="event?.timeline"
                                     :shifts="event?.shifts"
                                     :crafts="crafts"
                                     :currentUserCrafts="currentUserCrafts"
                                     :event="event?.event"
                                     :shift-qualifications="shiftQualifications"
                                     @dropFeedback="dropFeedback"
                                     :shift-time-presets="shiftTimePresets"
            />
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
import IconLib from "@/Mixins/IconLib.vue";
import BaseMenu from "@/Components/Menu/BaseMenu.vue";

export default defineComponent({
    name: "SingleRelevantEvent",
    props: [
        'event',
        'crafts',
        'eventTypes',
        'currentUserCrafts',
        'shiftQualifications',
        'shiftTimePresets'
    ],
    emits: ['dropFeedback'],
    mixins: [IconLib],
    components: {
        BaseMenu,
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
            showShift: this.$page.props.urlParameters?.eventId ? parseInt(this.$page.props.urlParameters?.eventId) === parseInt(this.event.event.id) : true,
            showImportShiftTemplateModal: false,
        }
    },
    methods: {
        closeImportShiftTemplateModal() {
            this.showImportShiftTemplateModal = false;
            this.$refs.timelineShiftsComponent.reinitializeEventContainerPlacements();
        },
        dropFeedback(event) {
            this.$emit('dropFeedback', event)
        },
        openDeleteConfirmModal() {
            this.showConfirmDeleteModal = true
        },
        closeConfirmDeleteModal() {
            this.showConfirmDeleteModal = false
        },
        deleteShift() {
            this.$inertia.delete(`/events/${this.event.event.id}/shifts`)
            this.showConfirmDeleteModal = false
        },
        saveShiftAsPreset() {
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
