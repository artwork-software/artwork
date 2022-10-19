<template>
    <div v-for="event in trashed_events"
         class="flex w-full bg-white my-2 border border-gray-200">
        <div class="flex mt-2 w-full ml-4 flex-wrap p-4">
            <div class="flex justify-between w-full">
                <div class="my-auto">
                    <p class="text-2xl leading-6 font-bold font-lexend text-gray-900" v-if="!event.name">
                        {{ event.project?.name || "Termin ohne Name" }}
                    </p>
                    <div v-else class="flex w-full items-center justify-between">
                        <div class="mr-12 text-2xl leading-6 font-bold font-lexend text-primary">
                            {{ event.name}}
                        </div>
                        <div v-if="event.project" class="mt-1.5 flex">
                            zugeordnet zu:
                            <a v-if="event.project?.id"
                               :href="route('projects.show', {project: event.project.id, openTab: 'calendar'})"
                               class="ml-3 text-md flex font-bold font-lexend text-primary">
                                {{ event.project.name}}
                            </a>
                        </div>
                    </div>
                    <p class="text-sm leading-6 font-lexend text-gray-500 mt-2">
                        {{ event.start }} - {{ event.end }}
                    </p>
                    <p class="text-sm leading-6 font-lexend text-gray-500 mt-2">
                        {{ event.event_type }}
                    </p>
                </div>
                <div class="flex items-center">
                    <Menu as="div" class="my-auto relative">
                        <div class="flex">
                            <MenuButton
                                class="flex ml-6">
                                <DotsVerticalIcon
                                    class="mr-3 flex-shrink-0 h-6 w-6 text-gray-600 my-auto"
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
                                              :href="route('events.restore', { id: event.id })"
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
                                              :href="route('events.force', { id: event.id })"
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
        </div>
    </div>
</template>

<script>
import AppLayout from "@/Layouts/AppLayout";
import TrashLayout from "@/Layouts/TrashLayout";
import {ChevronDownIcon, ChevronUpIcon, DotsVerticalIcon, RefreshIcon} from "@heroicons/vue/solid";
import {Menu, MenuButton, MenuItem, MenuItems} from "@headlessui/vue";
import {TrashIcon} from "@heroicons/vue/outline";
import {Link} from "@inertiajs/inertia-vue3";

export default {
    name: "Events",
    layout: [AppLayout, TrashLayout],
    props: ['trashed_events'],
    components: {
        ChevronDownIcon,
        ChevronUpIcon,
        Menu, MenuButton, DotsVerticalIcon,
        MenuItems,MenuItem, RefreshIcon, TrashIcon, Link
    },
    data() {
        return {
            showTemporaryEvents: [],
        }
    },
    methods: {
        switchVisibility(eventId) {
            if (this.showTemporaryEvents.includes(eventId)) {
                this.showTemporaryEvents.splice(this.showTemporaryEvents.indexOf(eventId), 1);
            } else {
                this.showTemporaryEvents.push(eventId);
            }
        },
    }
}
</script>

<style scoped>

</style>
