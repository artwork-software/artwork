<template>
    <div class="flex w-full justify-between">
        <div>

        </div>
        <div class="flex justify-end items-center ml-8 -mt-14">
            <div v-if="!showSearchbar" @click="openSearchbar"
                 class="cursor-pointer inset-y-0 mr-3">
                <SearchIcon class="h-5 w-5" aria-hidden="true"/>
            </div>
            <div v-else class="flex items-center w-64 mr-2">
                <div>
                    <input type="text"
                           ref="searchBarInput"
                           :placeholder="$t('Search')"
                           v-model="searchText"
                           class="h-10 sDark inputMain rounded-lg placeholder:xsLight placeholder:subpixel-antialiased focus:outline-none focus:ring-0 focus:border-secondary focus:border-1 w-full border-gray-300"/>
                </div>
                <XIcon class="ml-2 cursor-pointer h-5 w-5" @click="closeSearchbar()"/>
            </div>
        </div>
    </div>
    <div v-for="area in filteredTrashedRooms" class="w-full">
        <div v-if="area.rooms.length > 0" class="flex w-full bg-white my-2 border border-gray-200">
            <button class="bg-artwork-buttons-create hover:bg-artwork-buttons-hover flex" @click="area.hidden = !area.hidden">
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
                </div>
                <div class="mt-6 mb-12" v-if="!area.hidden">
                    <div @mouseover="showMenu = room.id"
                         @mouseout="showMenu = null" v-for="room in area.rooms" :key="room.id">
                        <div  v-show="!room.temporary" class="flex">
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
                                    <BaseMenu :key="room.id"
                                              v-show="showMenu === room.id">
                                        <MenuItem v-slot="{ active }">
                                            <Link as="button" method="patch"
                                                  :href="route('rooms.restore', { id: room.id })"
                                                  :class="[active ? 'bg-artwork-navigation-color/10 text-artwork-buttons-hover' :
                                          'text-secondary',
                                          'group flex items-center px-4 py-2 w-full text-sm subpixel-antialiased']">
                                                <RefreshIcon
                                                    class="mr-3 h-5 w-5 text-primaryText group-hover:text-artwork-buttons-hover"
                                                    aria-hidden="true"/>
                                                {{ $t('Restore')}}
                                            </Link>
                                        </MenuItem>
                                        <MenuItem v-slot="{ active }">
                                            <Link as="button" method="delete"
                                                  :href="route('rooms.force', { id: room.id })"
                                                  :class="[active ? 'bg-artwork-navigation-color/10 text-artwork-buttons-hover' :
                                          'text-secondary',
                                          'group flex items-center px-4 py-2 w-full text-sm subpixel-antialiased']">
                                                <TrashIcon
                                                    class="mr-3 h-5 w-5 text-primaryText group-hover:text-artwork-buttons-hover"
                                                    aria-hidden="true"/>
                                                {{ $t('Delete permanently')}}
                                            </Link>
                                        </MenuItem>
                                    </BaseMenu>
                                </div>

                            </div>
                        </div>
                    </div>

                    <h2 v-on:click="switchVisibility(area.id)"
                        class="text-sm mt-10 pb-2 flex font-bold text-primary cursor-pointer">
                        {{ $t('Temporary rooms')}}
                        <ChevronUpIcon v-if="showTemporaryRooms.includes(area.id)"
                                       class=" ml-1 mr-3 flex-shrink-0 mt-1 h-4 w-4"></ChevronUpIcon>
                        <ChevronDownIcon v-else
                                         class=" ml-1 mr-3 flex-shrink-0 mt-1 h-4 w-4"></ChevronDownIcon>
                    </h2>

                    <div v-show="showTemporaryRooms.includes(area.id)">
                        <div  @mouseover="showMenu = room.id"
                              @mouseout="showMenu = null" v-for="room in area.rooms" :key="room.id">
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
                                    <BaseMenu>
                                        <MenuItem v-slot="{ active }">
                                            <Link as="button" method="patch"
                                                  :href="route('rooms.restore', { id: room.id })"
                                                  :class="[active ? 'bg-artwork-navigation-color/10 text-artwork-buttons-hover' :
                                          'text-secondary',
                                          'group flex items-center px-4 py-2 w-full text-sm subpixel-antialiased']">
                                                <RefreshIcon
                                                    class="mr-3 h-5 w-5 text-primaryText group-hover:text-artwork-buttons-hover"
                                                    aria-hidden="true"/>
                                                {{$t('Restore')}}
                                            </Link>
                                        </MenuItem>
                                        <MenuItem v-slot="{ active }">
                                            <Link as="button" method="delete"
                                                  :href="route('rooms.force', { id: room.id })"
                                                  :class="[active ? 'bg-artwork-navigation-color/10 text-artwork-buttons-hover' :
                                          'text-secondary',
                                          'group flex items-center px-4 py-2 w-full text-sm subpixel-antialiased']">
                                                <TrashIcon
                                                    class="mr-3 h-5 w-5 text-primaryText group-hover:text-artwork-buttons-hover"
                                                    aria-hidden="true"/>
                                                {{ $t('Delete permanently')}}
                                            </Link>
                                        </MenuItem>
                                    </BaseMenu>
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
import AppLayout from "@/Layouts/AppLayout.vue";
import TrashLayout from "@/Layouts/TrashLayout.vue";
import {ChevronUpIcon, ChevronDownIcon, DotsVerticalIcon, RefreshIcon, SearchIcon} from "@heroicons/vue/solid";
import {TrashIcon, XIcon} from "@heroicons/vue/outline";
import {Menu, MenuButton,MenuItems,MenuItem } from "@headlessui/vue";
import { Link } from "@inertiajs/vue3";
import Input from "@/Layouts/Components/InputComponent.vue";
import BaseMenu from "@/Components/Menu/BaseMenu.vue";

export default {
    name: "Projects",
    layout: [AppLayout, TrashLayout],
    props: ['trashed_rooms'],
    components: {
        BaseMenu,
        Input, XIcon, SearchIcon,
        ChevronDownIcon,
        ChevronUpIcon,
        Menu, MenuButton, DotsVerticalIcon,
        MenuItems,MenuItem, RefreshIcon, TrashIcon, Link
    },
    data() {
      return {
          showMenu: null,
          showTemporaryRooms: [],
          showSearchbar: false,
          searchText: '',
      }
    },
    computed: {
        filteredTrashedRooms() {
            if (!this.searchText){
                return this.trashed_rooms;
            }
            return this.trashed_rooms.filter((area) => {
                return area.name.toLowerCase().includes(this.searchText.toLowerCase())
            })
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
        closeSearchbar() {
            this.showSearchbar = false
            this.searchText = ''
        },
        openSearchbar(){
            this.showSearchbar = !this.showSearchbar;
            this.$nextTick(() => {
                if (this.showSearchbar) {
                    this.$refs.searchBarInput.focus();
                }
            });
        },
    }
}
</script>

<style scoped>

</style>
