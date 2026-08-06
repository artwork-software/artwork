<template>
    <TrashSearchAndActions
        property-name="trashed_rooms"
        :total="trashed_rooms.total"
        @delete-all="showConfirmDeleteAll = true"
    />
    <div v-for="area in trashed_rooms.data" :key="area.id" class="w-full">
        <div v-if="area.rooms.length > 0" class="flex w-full bg-white my-2 border border-border-subtle">
            <button class="bg-accent-600 hover:bg-accent-700 flex" @click="area.hidden = !area.hidden">
                <IconChevronUp v-if="area.hidden !== true"
                               class="h-6 w-6 text-white my-auto"></IconChevronUp>
                <IconChevronDown v-else
                                 class="h-6 w-6 text-white my-auto"></IconChevronDown>
            </button>
            <div class="flex mt-8 w-full ml-4 flex-wrap p-4">
                <div class="flex justify-between w-full">
                    <div class="my-auto">
                                        <span class="text-2xl leading-6 font-bold font-lexend text-text">
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
                                        <div class="ml-6 flex items-center text-text-subtle text-sm my-auto">
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
                                                  :class="[active ? 'bg-text-inverse/10 text-accent-700' :
                                          'text-text-subtle',
                                          'group flex items-center px-4 py-2 w-full text-sm subpixel-antialiased']">
                                                <IconRefresh
                                                    class="mr-3 h-5 w-5 text-primaryText group-hover:text-accent-700"
                                                    aria-hidden="true"/>
                                                {{ $t('Restore')}}
                                            </Link>
                                        </MenuItem>
                                        <MenuItem v-slot="{ active }">
                                            <Link as="button" method="delete"
                                                  :href="route('rooms.force', { id: room.id })"
                                                  :class="[active ? 'bg-text-inverse/10 text-accent-700' :
                                          'text-text-subtle',
                                          'group flex items-center px-4 py-2 w-full text-sm subpixel-antialiased']">
                                                <IconTrash
                                                    class="mr-3 h-5 w-5 text-primaryText group-hover:text-accent-700"
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
                        class="text-sm mt-10 pb-2 flex font-bold text-text cursor-pointer">
                        {{ $t('Temporary rooms')}}
                        <IconChevronUp v-if="showTemporaryRooms.includes(area.id)"
                                       class=" ml-1 mr-3 flex-shrink-0 mt-1 h-4 w-4"></IconChevronUp>
                        <IconChevronDown v-else
                                         class=" ml-1 mr-3 flex-shrink-0 mt-1 h-4 w-4"></IconChevronDown>
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
                                            class="ml-6 flex items-center text-text-subtle text-sm my-auto">
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
                                                  :class="[active ? 'bg-text-inverse/10 text-accent-700' :
                                          'text-text-subtle',
                                          'group flex items-center px-4 py-2 w-full text-sm subpixel-antialiased']">
                                                <IconRefresh
                                                    class="mr-3 h-5 w-5 text-primaryText group-hover:text-accent-700"
                                                    aria-hidden="true"/>
                                                {{$t('Restore')}}
                                            </Link>
                                        </MenuItem>
                                        <MenuItem v-slot="{ active }">
                                            <Link as="button" method="delete"
                                                  :href="route('rooms.force', { id: room.id })"
                                                  :class="[active ? 'bg-text-inverse/10 text-accent-700' :
                                          'text-text-subtle',
                                          'group flex items-center px-4 py-2 w-full text-sm subpixel-antialiased']">
                                                <IconTrash
                                                    class="mr-3 h-5 w-5 text-primaryText group-hover:text-accent-700"
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

    <BasePaginator
        v-if="trashed_rooms.total > 0"
        :entities="trashed_rooms"
        property-name="trashed_rooms"
        class="mt-6"
    />

    <ConfirmDeleteModal
        v-if="showConfirmDeleteAll"
        :title="$t('Delete all')"
        :description="$t('Are you sure you want to permanently delete all items in the recycle bin for this category?')"
        @closed="showConfirmDeleteAll = false"
        @delete="forceDeleteAll"
    />
</template>

<script>
import {IconChevronDown, IconChevronUp, IconDotsVertical, IconRefresh, IconSearch, IconTrash, IconX} from "@tabler/icons-vue";
import AppLayout from "@/Layouts/AppLayout.vue";
import TrashLayout from "@/Layouts/TrashLayout.vue";
import {Menu, MenuButton,MenuItems,MenuItem } from "@headlessui/vue";
import { Link } from "@inertiajs/vue3";
import Input from "@/Layouts/Components/InputComponent.vue";
import BaseMenu from "@/Components/Menu/BaseMenu.vue";
import ConfirmDeleteModal from "@/Layouts/Components/ConfirmDeleteModal.vue";
import TrashSearchAndActions from "@/Pages/Trash/Components/TrashSearchAndActions.vue";
import BasePaginator from "@/Components/Paginate/BasePaginator.vue";

export default {
    name: "Projects",
    layout: [AppLayout, TrashLayout],
    props: ['trashed_rooms'],
    components: {
        BaseMenu,
        BasePaginator,
        TrashSearchAndActions,
        ConfirmDeleteModal,
        Input, IconX, IconSearch,
        IconChevronDown,
        IconChevronUp,
        Menu, MenuButton, IconDotsVertical,
        MenuItems,MenuItem, IconRefresh, IconTrash, Link
    },
    data() {
      return {
          showMenu: null,
          showTemporaryRooms: [],
          showConfirmDeleteAll: false,
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
        forceDeleteAll() {
            this.$inertia.delete(route('rooms.force.all'), {
                onSuccess: () => {
                    this.showConfirmDeleteAll = false;
                }
            });
        },
    }
}
</script>

<style scoped>

</style>
