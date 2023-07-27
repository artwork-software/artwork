<template>
    <div class=" flex items-center justify-between px-4 text-white text-xs relative bg-gray-500">
        <div class="h-9 flex items-center">
            {{ shift.craft.abbreviation }}
            (0/{{ shift.number_employees }})
            <span class="ml-1" v-if="shift.number_masters > 0">
                 (0/{{ shift.number_masters }})
            </span>
        </div>
        <div class="absolute flex items-center right-0">
            <div v-if="!shift.break_minutes" class="h-9 bg-red-500 flex items-center w-fit right-0 p-3">
                <svg xmlns="http://www.w3.org/2000/svg" width="12.21" height="12.2" viewBox="0 0 12.21 12.2">
                    <g id="Gruppe_1639" data-name="Gruppe 1639" transform="translate(-523.895 -44.9)" opacity="0.9">
                        <path id="Icon_metro-warning" data-name="Icon metro-warning"
                              d="M8.571,3.015,13.6,13.037H3.542L8.571,3.015Zm0-1.087a.867.867,0,0,0-.713.523L2.735,12.66c-.392.7-.059,1.268.742,1.268H13.664c.8,0,1.134-.571.742-1.268h0L9.284,2.451A.867.867,0,0,0,8.571,1.928Zm.75,9.75a.75.75,0,1,1-.75-.75A.75.75,0,0,1,9.321,11.678Zm-.75-1.5a.75.75,0,0,1-.75-.75V7.178a.75.75,0,1,1,1.5,0v2.25A.75.75,0,0,1,8.571,10.178Z"
                              transform="translate(521.429 43.072)" fill="#fcfcfb" stroke="#fcfcfb" stroke-width="0.2"/>
                    </g>
                </svg>
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
                                        <DuplicateIcon
                                            class="mr-3 h-5 w-5 text-primaryText group-hover:text-white"
                                            aria-hidden="true"/>
                                        Bearbeiten
                                    </a>
                                </MenuItem>
                                <MenuItem v-slot="{ active }">
                                    <a href="#" @click="deleteShift(shift.id)"
                                       :class="[active ? 'bg-primaryHover text-white' : 'text-secondary', 'group flex items-center px-4 py-2 text-sm subpixel-antialiased']">
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
    <div class="mt-1 h-[calc(100%-2.7rem)] bg-gray-200 p-1 max-h-96 overflow-x-scroll">
        <p class="text-xs mb-1">
            {{ shift.start }} - {{ shift.end }}
            <span v-if="shift.break_minutes">| {{ shift.break_formatted }}</span>
        </p>
        <p class="text-xs mb-3">{{ shift.description }}</p>
        <div v-for="(user) in shift.number_masters">
            <div class="flex items-center gap-2 p-1 hover:bg-gray-50/40 rounded cursor-pointer">
                <span class="h-4 w-4 rounded-full block bg-gray-500"></span>
                <span class="text-xs">Unbesetzt</span>
                <span>
                    <svg xmlns="http://www.w3.org/2000/svg" width="13.2" height="10.8" viewBox="0 0 13.2 10.8">
                        <path id="Icon_awesome-crown" data-name="Icon awesome-crown" d="M9.9,8.4H2.1a.3.3,0,0,0-.3.3v.6a.3.3,0,0,0,.3.3H9.9a.3.3,0,0,0,.3-.3V8.7A.3.3,0,0,0,9.9,8.4Zm1.2-6a.9.9,0,0,0-.9.9.882.882,0,0,0,.083.371l-1.358.814A.6.6,0,0,1,8.1,4.268L6.568,1.594a.9.9,0,1,0-1.136,0L3.9,4.267a.6.6,0,0,1-.829.218L1.719,3.671A.9.9,0,1,0,.9,4.2a.919.919,0,0,0,.144-.015L2.4,7.8H9.6l1.356-3.615A.919.919,0,0,0,11.1,4.2a.9.9,0,0,0,0-1.8Z" transform="translate(0.6 0.6)" fill="none" stroke="#82818a" stroke-width="1.2"/>
                    </svg>
                </span>
            </div>
        </div>
        <div v-for="(user) in shift.number_employees">
            <div class="flex items-center gap-2 p-1 hover:bg-gray-50/40 rounded cursor-pointer">
                <span class="h-4 w-4 rounded-full block bg-gray-500"></span>
                <span class="text-xs">Unbesetzt</span>
            </div>
        </div>
    </div>

    <AddEditShiftPresetModal :preset="shift" v-if="showEditShiftModal" @closed="showEditShiftModal = false"/>
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

export default defineComponent({
    name: "SinglePresetShift",
    computed: {
        dayjs() {
            return dayjs
        }
    },
    data(){
        return {
            showEditShiftModal: false
        }
    },
    components: {
        AddEditShiftPresetModal,
        AddShiftModal, DropElement, XIcon, DotsVerticalIcon, TrashIcon, DuplicateIcon,
        Menu, MenuItems, MenuItem, MenuButton
    },
    props: ['shift'],
    methods: {
        deleteShift(){
            this.$inertia.delete(route('preset.shift.destroy', {presetShift: this.shift.id}))
        },
        editPreset(){

        }
    }
})
</script>


<style scoped>

</style>
