<template>
    <div v-for="area in trashed_areas"
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
                                class="flex bg-tagBg p-0.5 rounded-full">
                                <DotsVerticalIcon
                                    class=" flex-shrink-0 h-6 w-6 text-menuButtonBlue my-auto"
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
                                        <Link as="button" method="patch"
                                              :href="route('areas.restore', { id: area.id })"
                                              :class="[active ? 'bg-primaryHover text-white' :
                                          'text-secondary',
                                          'group flex items-center px-4 py-2 w-full text-sm subpixel-antialiased']">
                                            <RefreshIcon
                                                class="mr-3 h-5 w-5 text-primaryText group-hover:text-white"
                                                aria-hidden="true"/>
                                            Wiederherstellen
                                        </Link>
                                    </MenuItem>
                                    <MenuItem v-slot="{ active }">
                                        <Link as="button" method="delete"
                                              :href="route('areas.force', { id: area.id })"
                                              :class="[active ? 'bg-primaryHover text-white' :
                                          'text-secondary',
                                          'group flex items-center px-4 py-2 w-full text-sm subpixel-antialiased']">
                                            <TrashIcon
                                                class="mr-3 h-5 w-5 text-primaryText group-hover:text-white"
                                                aria-hidden="true"/>
                                            Endgültig löschen
                                        </Link>
                                    </MenuItem>
                                </div>
                            </MenuItems>
                        </transition>
                    </Menu>
                </div>
            </div>
            <div class="mt-6 mb-12" v-if="!area.hidden">
                <div v-for="room in area.rooms" :key="room.id">
                    <div v-show="!room.temporary" class="flex">
                        <div class="flex mt-6 flex-wrap w-full">
                            <div class="flex w-full">
                                <div class="flex">
                                    <div class="ml-4 my-auto text-lg font-black text-sm">
                                        {{ room.name }}
                                    </div>
                                    <div class="ml-6 flex items-center text-secondary text-sm my-auto">
                                        angelegt am {{ room.created_at }} von
                                        <img :src="room.created_by.profile_photo_url"
                                             :alt="room.created_by.first_name"
                                             class="rounded-full ml-2 h-6 w-6 object-cover cursor-pointer">
                                    </div>
                                </div>
                            </div>

                        </div>
                    </div>
                </div>

                <h2 v-on:click="switchVisibility(area.id)"
                    class="text-sm mt-10 pb-2 flex font-bold text-primary cursor-pointer">
                    Temporäre
                    Räume
                    <ChevronUpIcon v-if="showTemporaryRooms.includes(area.id)"
                                   class=" ml-1 mr-3 flex-shrink-0 mt-1 h-4 w-4"></ChevronUpIcon>
                    <ChevronDownIcon v-else
                                     class=" ml-1 mr-3 flex-shrink-0 mt-1 h-4 w-4"></ChevronDownIcon>
                </h2>

                <div v-show="showTemporaryRooms.includes(area.id)">
                    <div v-for="room in area.rooms" :key="room.id">
                        <div v-show="room.temporary" class="flex mt-6">
                            <div class="flex w-full">
                                <div class="flex">
                                    <div class="ml-4 my-auto text-lg font-black text-sm">
                                        {{ room.name }} ({{ room.start_date }}
                                        - {{ room.end_date }})
                                    </div>

                                    <div
                                        class="ml-6 flex items-center text-secondary text-sm my-auto">
                                        angelegt am {{ room.created_at }} von
                                        <img
                                            :src="room.created_by.profile_photo_url"
                                            :alt="room.created_by.first_name"
                                            class="rounded-full ml-2 h-6 w-6 object-cover cursor-pointer">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import AppLayout from "@/Layouts/AppLayout";
import TrashLayout from "@/Layouts/TrashLayout";
import { ChevronUpIcon, ChevronDownIcon, DotsVerticalIcon, RefreshIcon } from "@heroicons/vue/solid";
import { TrashIcon } from "@heroicons/vue/outline";
import {Menu, MenuButton,MenuItems,MenuItem } from "@headlessui/vue";
import { Link } from "@inertiajs/inertia-vue3";

export default {
    name: "Projects",
    layout: [AppLayout, TrashLayout],
    props: ['trashed_areas'],
    components: {
        ChevronDownIcon,
        ChevronUpIcon,
        Menu, MenuButton, DotsVerticalIcon,
        MenuItems,MenuItem, RefreshIcon, TrashIcon, Link
    },
    data() {
      return {
          showTemporaryRooms: [],
      }
    },
    methods: {
        switchVisibility(areaId) {
            if (this.showTemporaryRooms.includes(areaId)) {
                this.showTemporaryRooms.splice(this.showTemporaryRooms.indexOf(areaId), 1);
            } else {
                this.showTemporaryRooms.push(areaId);
            }
        },
    }
}
</script>

<style scoped>

</style>
