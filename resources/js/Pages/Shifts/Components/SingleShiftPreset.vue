<template>
    <div class="mb-3">
        <div class="w-full h-12 flex items-center justify-between px-4 text-white text-sm" :class="preset.event_type.svg_name">
            <div class="flex items-center uppercase">
                {{ preset.event_type.abbreviation }} | {{ preset.name }}
                <div class="ml-4 cursor-pointer" @click="showShift = !showShift">
                    <ChevronDownIcon class="h-4 w-4" v-if="!showShift"/>
                    <ChevronUpIcon class="h-4 w-4" v-else/>
                </div>
            </div>
            <div>
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
                            class="origin-top-right z-100 absolute right-0 mr-4 mt-2 w-72 shadow-lg bg-zinc-800 ring-1 ring-black ring-opacity-5 divide-y divide-gray-100 focus:outline-none">
                            <div class="py-1">
                                <MenuItem v-slot="{ active }">
                                    <a href="#" @click="showEditShiftModal = true"
                                       :class="[active ? 'bg-primaryHover text-white' : 'text-secondary', 'group flex items-center px-4 py-2 text-sm subpixel-antialiased']">
                                        <img src="/Svgs/IconSvgs/icon_menu_item.svg" class="w-5 h-5 mr-3"  alt="">
                                        {{ $t('Redefine template')}}
                                    </a>
                                </MenuItem>
                                <MenuItem v-slot="{ active }">
                                    <a href="#" @click="duplicatePreset"
                                       :class="[active ? 'bg-primaryHover text-white' : 'text-secondary', 'group flex items-center px-4 py-2 text-sm subpixel-antialiased']">
                                        <img src="/Svgs/IconSvgs/icon_menu_item.svg" class="w-5 h-5 mr-3"  alt="">
                                        {{ $t('Duplicate template') }}
                                    </a>
                                </MenuItem>
                                <MenuItem v-slot="{ active }">
                                    <a href="#" @click="showConfirmDeleteModal = true"
                                       :class="[active ? 'bg-primaryHover text-white' : 'text-secondary', 'group flex items-center px-4 py-2 text-sm subpixel-antialiased']">
                                        <img src="/Svgs/IconSvgs/icon_menu_item.svg" class="w-5 h-5 mr-3"  alt="">
                                        {{ $t('Delete template')}}
                                    </a>
                                </MenuItem>
                            </div>
                        </MenuItems>
                    </transition>
                </Menu>
            </div>
        </div>
        <div class="flex justify-start mt-3 overflow-x-scroll gap-3 h-full" v-if="showShift">
            <PresetTimeLine :time-line="preset.timeline" :preset-id="preset.id" />
            <div class="w-[175px]" v-for="presetShift in preset.shifts">
                <SinglePresetShift :preset-shift="presetShift" :shift-qualifications="shiftQualifications"/>
            </div>
            <div class="w-[175px] flex items-center justify-center border-2 border-dashed"
                 @click="showAddShiftPresetModal = true">
                <PlusCircleIcon class="h-4 w-4 rounded-full bg-backgroundBlue"/>
            </div>
        </div>
    </div>
    <AddEditShiftPresetModal v-if="showAddShiftPresetModal"
                             :crafts="crafts"
                             :preset-id="preset.id"
                             :shift-qualifications="shiftQualifications"
                             @closed="showAddShiftPresetModal = false"
    />
    <AddShiftPresetModal v-if="showEditShiftModal"
                         :event_types="event_types"
                         :preset="preset"
                         @closed="showEditShiftModal = false"
    />
    <ConfirmDeleteModal v-if="showConfirmDeleteModal"
                        :title="$t('Delete shift template')"
                        :description="$t('Would you like to delete the shift template?')"
                        @closed="showConfirmDeleteModal = false"
                        @delete="deletePreset"
    />
</template>
<script>
import {defineComponent} from 'vue'
import TimeLineShiftsComponent from "@/Pages/Projects/Components/TimeLineShiftsComponent.vue";
import {
    ChevronDownIcon,
    ChevronUpIcon,
    DotsVerticalIcon,
    DuplicateIcon,
    PlusCircleIcon,
    TrashIcon,
    XIcon
} from "@heroicons/vue/outline";
import ConfirmDeleteModal from "@/Layouts/Components/ConfirmDeleteModal.vue";
import AddShiftPresetModal from "@/Pages/Projects/Components/AddShiftPresetModal.vue";
import PresetTimeLine from "@/Pages/Shifts/Components/PresetTimeLine.vue";
import SingleShift from "@/Pages/Projects/Components/SingleShift.vue";
import SinglePresetShift from "@/Pages/Shifts/Components/SinglePresetShift.vue";
import AddEditShiftPresetModal from "@/Pages/Shifts/Components/AddEditShiftPresetModal.vue";
import {Menu, MenuButton, MenuItem, MenuItems} from "@headlessui/vue";
import SvgCollection from "@/Layouts/Components/SvgCollection.vue";

export default defineComponent({
    name: "SingleShiftPreset",
    components: {
        SvgCollection,
        TrashIcon,
        DuplicateIcon,
        AddEditShiftPresetModal,
        SinglePresetShift,
        PlusCircleIcon,
        SingleShift,
        PresetTimeLine,
        ChevronDownIcon,
        AddShiftPresetModal,
        ConfirmDeleteModal,
        ChevronUpIcon,
        XIcon,
        DotsVerticalIcon,
        TimeLineShiftsComponent,
        Menu,
        MenuItems,
        MenuItem,
        MenuButton
    },
    props: [
        'preset',
        'crafts',
        'event_types',
        'shiftQualifications'
    ],
    data() {
        return {
            showShift: false,
            showConfirmDeleteModal: false,
            showAddShiftPresetModal: false,
            showEditShiftModal: false
        }
    },
    methods: {
        deletePreset(){
            this.$inertia.delete(route('destroy.shift.preset', {shiftPreset: this.preset.id}), {
                preserveScroll: true,
                onSuccess: () => this.showConfirmDeleteModal = false
            })
        },
        duplicatePreset(){
            this.$inertia.post(route('duplicate.shift.preset', {shiftPreset: this.preset.id}), {
                preserveScroll: true
            })
        },
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
