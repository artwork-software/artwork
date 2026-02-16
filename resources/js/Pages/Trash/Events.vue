<template>
    <div class="flex w-full justify-between">
        <div>

        </div>
        <div class="flex justify-end items-center ml-8 -mt-14">
            <div v-if="!showSearchbar" @click="openSearchbar"
                 class="cursor-pointer inset-y-0 mr-3">
                <SearchIcon class="h-5 w-5" aria-hidden="true"/>
            </div>
            <button v-if="trashed_events.length > 0" @click="showConfirmDeleteAll = true"
                    class="cursor-pointer text-red-500 hover:text-red-700 mr-3">
                <TrashIcon class="h-5 w-5" aria-hidden="true"/>
            </button>
            <div v-else class="flex items-center w-64 mr-2">
                <div>
                    <input type="text"
                           :placeholder="$t('Search')"
                           v-model="searchText"
                           ref="searchBarInput"
                           class="h-10 sDark inputMain rounded-lg placeholder:xsLight placeholder:subpixel-antialiased focus:outline-none focus:ring-0 focus:border-secondary focus:border-1 w-full border-gray-300"/>
                </div>
                <XIcon class="ml-2 cursor-pointer h-5 w-5" @click="closeSearchbar()"/>
            </div>
        </div>
    </div>
    <div v-for="event in filteredTrashedEvents"
         class="flex w-full bg-white my-2 border border-gray-200">
        <div class="flex mt-2 w-full ml-4 flex-wrap p-4">
            <div class="flex justify-between w-full">
                <div class="my-auto">
                    <p class="headline2" v-if="!event.name">
                        {{ event.project?.name || this.$t('Event without name') }}
                    </p>

                    <div v-else class="flex w-full items-center justify-between">
                        <div class="mr-12 headline2">
                            {{ event.name }}
                        </div>
                        <div v-if="event.project" class="mt-1.5 flex">
                            {{ $t('assigned to')}}:
                            <a v-if="event.project?.id"
                               :href="route('projects.tab', {project: event.project.id, projectTab: this.first_project_calendar_tab_id})"
                               class="ml-3 text-md flex font-bold font-lexend text-primary">
                                {{ event.project.name }}
                            </a>
                        </div>
                    </div>
                    <p class="xsLight subpixel-antialiased mt-2">
                        {{ event.start }} - {{ event.end }}
                    </p>
                    <div class="text-sm leading-6 font-lexend text-gray-500 mt-2 flex items-center">
                        <div>
                            <div class="block w-3 h-3 rounded-full" :style="{'backgroundColor' : event.event_type.hex_code }" />
                        </div>
                        <p class="ml-1">{{ event.event_type.name }}</p>
                    </div>
                </div>
                <div class="flex items-center">
                    <BaseMenu>
                        <MenuItem v-slot="{ active }">
                            <Link as="button" method="patch"
                                  :href="route('events.restore', { id: event.id })"
                                  :class="[active ? 'bg-artwork-navigation-color/10 text-artwork-buttons-hover' :
                                          'text-secondary',
                                          'group flex items-center px-4 py-2 w-full text-sm subpixel-antialiased']">
                                <RefreshIcon
                                    class="mr-3 h-5 w-5 text-primaryText group-hover:text-artwork-buttons-hover"
                                    aria-hidden="true"/>
                                {{ $t('Restore') }}
                            </Link>
                        </MenuItem>
                        <MenuItem v-slot="{ active }">
                            <Link as="button" method="delete"
                                  :href="route('events.force', { id: event.id })"
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

    <ConfirmDeleteModal
        v-if="showConfirmDeleteAll"
        :title="$t('Delete all')"
        :description="$t('Are you sure you want to permanently delete all items in the recycle bin for this category?')"
        @closed="showConfirmDeleteAll = false"
        @delete="forceDeleteAll"
    />
</template>

<script>
import AppLayout from "@/Layouts/AppLayout.vue";
import TrashLayout from "@/Layouts/TrashLayout.vue";
import {ChevronDownIcon, ChevronUpIcon, DotsVerticalIcon, RefreshIcon, SearchIcon} from "@heroicons/vue/solid";
import {Menu, MenuButton, MenuItem, MenuItems} from "@headlessui/vue";
import {TrashIcon, XIcon} from "@heroicons/vue/outline";
import {Link} from "@inertiajs/vue3";
import Input from "@/Layouts/Components/InputComponent.vue";
import BaseMenu from "@/Components/Menu/BaseMenu.vue";
import ConfirmDeleteModal from "@/Layouts/Components/ConfirmDeleteModal.vue";

export default {
    name: "Events",
    layout: [AppLayout, TrashLayout],
    props: ['trashed_events', 'first_project_calendar_tab_id'],
    components: {
        BaseMenu,
        ConfirmDeleteModal,
        Input,
        XIcon,
        SearchIcon,
        ChevronDownIcon,
        ChevronUpIcon,
        Menu,
        MenuButton,
        DotsVerticalIcon,
        MenuItems,
        MenuItem,
        RefreshIcon,
        TrashIcon,
        Link
    },
    data() {
        return {
            showTemporaryEvents: [],
            showSearchbar: false,
            searchText: '',
            showConfirmDeleteAll: false,
        }
    },
    computed: {
        filteredTrashedEvents() {
            if(!this.searchText){
                return this.trashed_events;
            }
            return this.trashed_events.filter(event => {
                return event.name.toLowerCase().includes(this.searchText.toLowerCase())
            })
        }
    },
    methods: {
        switchVisibility(eventId) {
            if (this.showTemporaryEvents.includes(eventId)) {
                this.showTemporaryEvents.splice(this.showTemporaryEvents.indexOf(eventId), 1);
            } else {
                this.showTemporaryEvents.push(eventId);
            }
        }
        ,
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
        forceDeleteAll() {
            this.$inertia.delete(route('events.force.all'), {
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
