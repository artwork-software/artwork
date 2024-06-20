<template>
    <div class=" flex items-center justify-between px-4 text-white text-xs relative bg-gray-500">
        <div class="h-9 flex items-center">
            {{ presetShift.craft.abbreviation }}
            (0/{{ computedMaxWorkerCount }})
        </div>
        <div class="absolute flex items-center right-0">
            <div v-if="!presetShift.break_minutes" class="h-9 bg-red-500 flex items-center w-fit right-0 p-3">
                <svg xmlns="http://www.w3.org/2000/svg" width="12.21" height="12.2" viewBox="0 0 12.21 12.2">
                    <g id="Gruppe_1639" data-name="Gruppe 1639" transform="translate(-523.895 -44.9)" opacity="0.9">
                        <path id="Icon_metro-warning" data-name="Icon metro-warning"
                              d="M8.571,3.015,13.6,13.037H3.542L8.571,3.015Zm0-1.087a.867.867,0,0,0-.713.523L2.735,12.66c-.392.7-.059,1.268.742,1.268H13.664c.8,0,1.134-.571.742-1.268h0L9.284,2.451A.867.867,0,0,0,8.571,1.928Zm.75,9.75a.75.75,0,1,1-.75-.75A.75.75,0,0,1,9.321,11.678Zm-.75-1.5a.75.75,0,0,1-.75-.75V7.178a.75.75,0,1,1,1.5,0v2.25A.75.75,0,0,1,8.571,10.178Z"
                              transform="translate(521.429 43.072)" fill="#fcfcfb" stroke="#fcfcfb" stroke-width="0.2"/>
                    </g>
                </svg>
            </div>
            <div>
                <BaseMenu dots-size="h-4 w-4">
                    <MenuItem v-slot="{ active }">
                        <a href="#" @click="showEditShiftModal = true"
                           :class="[active ? 'bg-artwork-navigation-color/10 text-white' : 'text-secondary', 'group flex items-center px-4 py-2 text-sm subpixel-antialiased capitalize']">
                            <IconEdit stroke-width="1.5"
                                      class="mr-3 h-5 w-5 text-primaryText group-hover:text-white"
                                      aria-hidden="true"/>
                            {{  $t('edit') }}
                        </a>
                    </MenuItem>
                    <MenuItem v-slot="{ active }">
                        <a href="#" @click="deleteShift(presetShift.id)"
                           :class="[active ? 'bg-artwork-navigation-color/10 text-white' : 'text-secondary', 'group flex items-center px-4 py-2 text-sm subpixel-antialiased']">
                            <IconTrash  stroke-width="1.5"
                                        class="mr-3 h-5 w-5 text-primaryText group-hover:text-white"
                                        aria-hidden="true"/>
                            {{ $t('Delete') }}
                        </a>
                    </MenuItem>
                </BaseMenu>
            </div>
        </div>
    </div>
    <div class="mt-1 h-[calc(100%-2.7rem)] bg-gray-200 p-1 max-h-96 overflow-x-scroll">
        <p class="text-xs mb-1">
            {{ presetShift.start }} - {{ presetShift.end }}
            <span v-if="presetShift.break_minutes">| {{ presetShift.break_formatted }}</span>
        </p>
        <p class="text-xs mb-3">{{ presetShift.description }}</p>
        <div v-for="shiftsQualification in this.presetShift.shifts_qualifications">
            <div v-for="(count) in shiftsQualification.value">
                <div class="flex items-center gap-2 p-1 hover:bg-gray-50/40 rounded cursor-pointer">
                    <span class="h-4 w-4 rounded-full block bg-gray-500"></span>
                    <span class="text-xs">{{ $t('Unoccupied')}}</span>
                    <ShiftQualificationIconCollection
                        :classes="'w-4 h-4'"
                        :icon-name="this.getShiftQualificationById(shiftsQualification.shift_qualification_id).icon"/>
                </div>
            </div>
        </div>
    </div>
    <AddEditShiftPresetModal v-if="showEditShiftModal"
                             :preset-shift="presetShift"
                             :shift-qualifications="shiftQualifications"
                             :edit="true"
                             :crafts="this.crafts"
                             @closed="showEditShiftModal = false"
    />
</template>
<script>
import {defineComponent} from 'vue'
import {DotsVerticalIcon, DuplicateIcon, TrashIcon} from "@heroicons/vue/outline";
import {XIcon} from "@heroicons/vue/solid";
import DropElement from "@/Pages/Projects/Components/DropElement.vue";
import AddShiftModal from "@/Pages/Projects/Components/AddShiftModal.vue";
import dayjs from "dayjs";
import {Menu, MenuItems, MenuItem, MenuButton} from "@headlessui/vue";
import AddEditShiftPresetModal from "@/Pages/Shifts/Components/AddEditShiftPresetModal.vue";
import {router} from "@inertiajs/vue3";
import ShiftQualificationIconCollection from "@/Layouts/Components/ShiftQualificationIconCollection.vue";
import IconLib from "@/Mixins/IconLib.vue";
import BaseMenu from "@/Components/Menu/BaseMenu.vue";

export default defineComponent({
    name: "SinglePresetShift",
    mixins: [IconLib],
    data(){
        return {
            showEditShiftModal: false
        }
    },
    components: {
        BaseMenu,
        ShiftQualificationIconCollection,
        AddEditShiftPresetModal,
        AddShiftModal,
        DropElement,
        XIcon,
        DotsVerticalIcon,
        TrashIcon,
        DuplicateIcon,
        Menu,
        MenuItems,
        MenuItem,
        MenuButton
    },
    props: [
        'presetShift',
        'shiftQualifications',
        'crafts'
    ],
    methods: {
        deleteShift(){
            router.delete(route('preset.shift.destroy', {presetShift: this.presetShift.id}))
        },
        getShiftQualificationById(id) {
            return this.shiftQualifications.find((shiftQualification) => shiftQualification.id === id);
        },
    },
    computed: {
        dayjs() {
            return dayjs
        },
        computedMaxWorkerCount() {
            let maxWorkerCount = 0;

            this.presetShift.shifts_qualifications.forEach(
                (shiftsQualification) => maxWorkerCount += shiftsQualification.value
            );

            return maxWorkerCount;
        },
    },
})
</script>
