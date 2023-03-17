<template>
    <div class="w-full flex flex-wrap">
        <div class="w-full flex justify-between items-center mt-4 mb-2 ml-4">
            <div class="inline-flex items-center">
                <Menu v-slot="{ open }" as="div" class="relative inline-block text-left w-auto">
                    <div>
                        <MenuButton id="menuButton"
                                    class="-mt-1 w-72 border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-black focus:outline-none focus-visible:ring-2 focus-visible:ring-white align-middle"
                        >
                            <CalendarIcon class="w-5 h-5 float-left mr-2"/>
                            <span class="float-left xsDark">HIER NOCH DATUM</span>
                            <ChevronDownIcon
                                class="ml-2 -mr-1 h-5 w-5 text-primary float-right"
                                aria-hidden="true"
                            />
                        </MenuButton>
                    </div>
                    <transition
                        enter-active-class="transition duration-50 ease-out"
                        enter-from-class="transform scale-100 opacity-100"
                        enter-to-class="transform scale-100 opacity-100"
                        leave-active-class="transition duration-75 ease-in"
                        leave-from-class="transform scale-100 opacity-100"
                        leave-to-class="transform scale-95 opacity-0"
                    >
                        <MenuItems
                            class="absolute left mt-2 w-52 origin-top-right rounded-sm bg-primary ring-1 ring-black py-2 text-white opacity-100 z-50">
                            <button @click="$refs.vuecal.switchView('day', new Date()); this.selectedDate = new Date();"
                                    class="w-full mt-2 text-left pl-2"
                                    :class="currentView === 'day' ? 'text-white font-bold border-l-2 border-success' : 'text-secondary border-none'">
                                <label class="text-sm">
                                    Heute
                                </label>
                            </button>
                            <button @click="$refs.vuecal.switchView('week')"
                                    class="w-full mt-2 text-left pl-2"
                                    :class="currentView === 'week' ? 'text-white font-bold border-l-2 border-success' : 'text-secondary border-none'">
                                <label class="text-sm">
                                    Woche
                                </label>
                            </button>
                            <button @click="$refs.vuecal.switchView('month')"
                                    class="w-full mt-2 text-left pl-2"
                                    :class="currentView === 'month' ? 'text-white font-bold border-l-2 border-l-success' : 'text-secondary border-none'">
                                <label class="text-sm">
                                    Monat
                                </label>
                            </button>
                            <button @click="$refs.vuecal.switchView('year')"
                                    class="w-full mt-2 text-left pl-2"
                                    :class="currentView === 'year' ? 'text-white font-bold border-l-2 border-l-success' : 'text-secondary border-none'">
                                <label class="text-sm">
                                    Jahr
                                </label>
                            </button>
                        </MenuItems>
                    </transition>
                </Menu>
                <button class="ml-2 -mt-2 text-black" @click="$refs.vuecal.previous()">
                    <ChevronLeftIcon class="h-5 w-5 text-primary"/>
                </button>
                <button class="ml-2 -mt-2 text-black" @click="$refs.vuecal.next()">
                    <ChevronRightIcon class="h-5 w-5 text-primary"/>
                </button>
            </div>
            <div class="flex items-center">
            <div class="flex items-center">
                <img src="/Svgs/IconSvgs/icon_zoom_in.svg" class="h-4 w-4 mx-2" />
                <img src="/Svgs/IconSvgs/icon_zoom_out.svg" class="h-6 w-6 mx-2" />
                <IndividualCalendarFilterComponent />
                <img src="/Svgs/IconSvgs/icon_settings.svg" class="h-6 w-6 mx-2" />
            </div>
                <button type="button" :class="'bg-buttonBlue hover:bg-buttonHover'"
                        class="flex p-2 px-8 mt-1 items-center border border-transparent rounded-full shadow-sm text-white hover:shadow-blueButton  focus:outline-none">
                    <PlusCircleIcon class="h-4 w-4 mr-2" aria-hidden="true"/>
                    <p class="text-sm">Neue Belegung</p>
                </button>
            </div>
        </div>

        <!-- Calendar -->
        <table class="w-full flex flex-wrap">
            <thead class="w-full">
            <tr class=" w-full flex bg-userBg">
                <th class="w-16">

                </th>
                <th v-for="room in rooms" class="w-52 py-3 border-r-4 border-secondaryHover">
                    <div class="flex calendarRoomHeader font-semibold items-center ml-4">
                            {{ room.name }}
                    </div>
                </th>
            </tr>
            </thead>
            <tbody class="flex w-full pt-3 flex-wrap">
            <tr class="w-full h-36 flex" v-for="day in days">
                <th class="w-16 eventTime text-secondary text-right -mt-2 pr-1">
                    {{ day }}
                </th>
                <td class="w-52 h-36 overflow-y-auto border-t-2 border-dashed" v-for="room in calendarData">
                    <div class="py-0.5 pr-2" v-for="event in room[day].data">
                        <SingleCalendarEvent :event="event"/>
                    </div>
                </td>
            </tr>
            </tbody>
        </table>
    </div>

</template>

<script>
import Button from "@/Jetstream/Button";
import {CalendarIcon, PlusCircleIcon} from '@heroicons/vue/outline'
import SingleCalendarEvent from "@/Layouts/Components/SingleCalendarEvent.vue";
import {
    Disclosure,
    DisclosureButton,
    DisclosurePanel,
    Menu,
    MenuButton,
    MenuItems,
} from "@headlessui/vue";
import {ChevronDownIcon, ChevronLeftIcon, ChevronRightIcon} from "@heroicons/vue/solid";
import IndividualCalendarFilterComponent from "@/Layouts/Components/IndividualCalendarFilterComponent.vue";



export default {
    name: "AddButton",
    components: {
        DisclosurePanel,
        DisclosureButton,
        Disclosure,
        Menu,
        MenuItems,
        MenuButton,
        Button,
        PlusCircleIcon,
        SingleCalendarEvent,
        ChevronLeftIcon,
        ChevronRightIcon,
        CalendarIcon,
        ChevronDownIcon,
        IndividualCalendarFilterComponent

    },
    data() {
      return {

      }
    },
    props: ['calendarData', 'rooms', 'days'],
    methods: {

    }
}
</script>

<style scoped>

</style>
