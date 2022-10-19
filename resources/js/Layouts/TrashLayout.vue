<template>
    <div class="max-w-screen-xl">
        <div class="flex-wrap max-w-5xl">
            <div class="flex flex-wrap mx-10">
                <h2 class="font-black text-primary font-lexend text-3xl w-full">Papierkorb</h2>
                <p class="text-secondary tracking-tight leading-6 subpixel-antialiased mt-5">Du kannst Objekte aus deinem Papierkorb wieder herstellen oder endgültig
                    löschen. Nach 30 Tagen werden Objekte automatisch endgültig gelöscht.</p>
                <div class="flex flex-wrap w-full">

                    <div class="w-full mt-5 flex my-auto justify-between">
                        <Listbox as="div" class="sm:col-span-3 mb-8" v-model="selectedTrash">
                            <div class="relative">
                                <ListboxButton class="flex cursor-pointer bg-white relative pr-14 font-semibold py-2 mt-4 text-left
                                focus:outline-none focus:ring-0 focus:ring-primary
                                focus:border-primary sm:text-sm">
                                        <span class="block truncate items-center primary">
                                            <span>{{ selectedTrash.name }}</span>
                                        </span>
                                    <span
                                        class="relative flex items-center pr-2">
                                     <ChevronDownIcon class="ml-2 h-5 w-5 text-primary" aria-hidden="true"/>
                                    </span>
                                </ListboxButton>

                                <transition leave-active-class="transition ease-in duration-100"
                                            leave-from-class="opacity-100" leave-to-class="opacity-0">
                                    <ListboxOptions
                                        class="absolute w-40 z-10 mt-1 w-full bg-primary shadow-lg max-h-32 rounded-md text-base ring-1
                                        ring-black ring-opacity-5 overflow-y-auto focus:outline-none sm:text-sm">
                                        <ListboxOption as="template" class="max-h-8"
                                                       v-for="page in trashSites"
                                                       :key="page.name"
                                                       :value="page"
                                                       v-slot="{ active, selected }">
                                            <li :class="[active ? 'bg-primaryHover text-white' : 'text-secondary', 'group cursor-pointer flex items-center justify-between p-3 text-sm subpixel-antialiased']">
                                                <span
                                                    :class="[selected ? 'font-bold text-white' : 'font-normal', 'block truncate']">
                                                    {{ page.name }}
                                                </span>
                                                <span :class="[active ? 'bg-primaryHover text-white' :
                                                    'text-secondary',
                                                    'group flex items-center text-sm subpixel-antialiased']">
                                                    <CheckIcon v-if="selected"
                                                               class="h-5 w-5 flex text-success"
                                                               aria-hidden="true"/>
                                                </span>
                                            </li>
                                        </ListboxOption>
                                    </ListboxOptions>
                                </transition>
                            </div>
                        </Listbox>

                        <div class="flex items-center">
                            <div class="inset-y-0 mr-3 pointer-events-none">
                                <SearchIcon class="h-5 w-5" aria-hidden="true"/>
                            </div>
                        </div>
                    </div>

                    <slot/>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import {Listbox, ListboxButton, ListboxOptions, ListboxOption} from "@headlessui/vue";
import {SearchIcon, ChevronDownIcon, CheckIcon} from "@heroicons/vue/solid";
import {Inertia} from "@inertiajs/inertia";

export default {
    name: "TrashLayout",
    data() {
        return {
            selectedTrash: null
        }
    },
    watch: {
      selectedTrash: {
          handler() {
              Inertia.get(this.selectedTrash.href)
          },
          deep: true
      }
    },
    created() {
      this.selectedTrash = this.trashSites[this.$page.component]
    },
    computed: {
        trashSites() {
            return {
                'Trash/Projects': {
                    name: 'Projekte',
                    href: route('projects.trashed')
                },
                'Trash/Areas': {
                    name: 'Areale',
                    href: route('areas.trashed')
                },
                'Trash/Rooms': {
                    name: 'Räume',
                    href: route('rooms.trashed')
                },
                'Trash/Events': {
                    name: 'Termine',
                    href: route('events.trashed')
                }
            }
        }
    },
    components: {
        Listbox,
        SearchIcon,
        ListboxButton,
        ListboxOptions,
        ListboxOption,
        CheckIcon,
        ChevronDownIcon
    }
}
</script>

<style scoped>

</style>
