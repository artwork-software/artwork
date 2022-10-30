<template>
    <div class="my-1 flex flex-wrap">
        <span v-for="attribute in calendarFilters.roomAttributes"
              class="flex rounded-full items-center font-medium text-tagText
              border bg-tagBg border-tag px-2 py-1 mt-1 text-sm mr-1 mb-1">
            {{ attribute.name }}
            <button
                @click="attribute.checked = false; this.$parent.changeFilterElements(calendarFilters.roomAttributes, attribute)"
                type="button">
                <XIcon class="ml-1 h-4 w-4 hover:text-error "/>
            </button>
        </span>
        <span v-for="category in calendarFilters.roomCategories"
              class="flex rounded-full items-center font-medium text-tagText
              border bg-tagBg border-tag px-2 py-1 mt-1 text-sm mr-1 mb-1">
            {{ category.name }}
            <button
                @click="category.checked = false; this.$parent.changeFilterElements(calendarFilters.roomCategories, category)"
                type="button">
                <XIcon class="ml-1 h-4 w-4 hover:text-error "/>
            </button>
        </span>
        <span v-for="room in calendarFilters.rooms"
              class="flex rounded-full items-center font-medium text-tagText
              border bg-tagBg border-tag px-2 py-1 mt-1 text-sm mr-1 mb-1">
            {{ room.label || room.name }}
            <button @click="room.checked = false; this.$parent.changeFilterElements(calendarFilters.rooms, room)"
                    type="button">
                <XIcon class="ml-1 h-4 w-4 hover:text-error "/>
            </button>
        </span>
        <span v-for="area in calendarFilters.areas"
              class="flex rounded-full items-center font-medium text-tagText
              border bg-tagBg border-tag px-2 py-1 mt-1 text-sm mr-1 mb-1">
            {{ area.label || area.name }}
            <button @click="area.checked = false; this.$parent.changeFilterElements(calendarFilters.areas, area)"
                    type="button">
                <XIcon class="ml-1 h-4 w-4 hover:text-error "/>
            </button>
        </span>
        <span v-for="eventType in calendarFilters.eventTypes"
              class="flex rounded-full items-center font-medium text-tagText
              border bg-tagBg border-tag px-2 py-1 mt-1 text-sm mr-1 mb-1">
            {{ eventType.name }}
            <button
                @click="eventType.checked = false; this.$parent.changeFilterElements(calendarFilters.eventTypes, eventType)"
                type="button">
                <XIcon class="ml-1 h-4 w-4 hover:text-error "/>
            </button>
        </span>
        <span v-if="calendarFilters.hasAudience"
              class="flex rounded-full items-center font-medium text-tagText
              border bg-tagBg border-tag px-2 py-1 mt-1 text-sm mr-1 mb-1">
            Mit Publikum
            <button @click="calendarFilters.hasAudience = !calendarFilters.hasAudience; eventAttributes.hasAudience.checked = false " type="button">
                <XIcon class="ml-1 h-4 w-4 hover:text-error "/>
            </button>
        </span>
        <span v-if="calendarFilters.hasNoAudience"
              class="flex rounded-full items-center font-medium text-tagText
              border bg-tagBg border-tag px-2 py-1 mt-1 text-sm mr-1 mb-1">
            ohne Publikum
            <button @click="calendarFilters.hasNoAudience = !calendarFilters.hasNoAudience; eventAttributes.hasNoAudience.checked = false" type="button">
                <XIcon class="ml-1 h-4 w-4 hover:text-error "/>
            </button>
        </span>
        <span v-if="calendarFilters.isLoud"
              class="flex rounded-full items-center font-medium text-tagText
              border bg-tagBg border-tag px-2 py-1 mt-1 text-sm mr-1 mb-1">
            laut
            <button @click="calendarFilters.isLoud = !calendarFilters.isLoud; eventAttributes.isLoud.checked = false" type="button">
                <XIcon class="ml-1 h-4 w-4 hover:text-error "/>
            </button>
        </span>
        <span v-if="calendarFilters.isNotLoud"
              class="flex rounded-full items-center font-medium text-tagText
              border bg-tagBg border-tag px-2 py-1 mt-1 text-sm mr-1 mb-1">
            nicht laut
            <button @click="calendarFilters.isNotLoud = !calendarFilters.isNotLoud; eventAttributes.isNotLoud.checked = false" type="button">
                <XIcon class="ml-1 h-4 w-4 hover:text-error "/>
            </button>
        </span>
        <span v-if="calendarFilters.adjoiningNoAudience"
              class="flex rounded-full items-center font-medium text-tagText
              border bg-tagBg border-tag px-2 py-1 mt-1 text-sm mr-1 mb-1">
            ohne Nebenveranstaltung mit Publikum
            <button @click="calendarFilters.adjoiningNoAudience = !calendarFilters.adjoiningNoAudience; eventAttributes.adjoiningNoAudience.checked = false" type="button">
                <XIcon class="ml-1 h-4 w-4 hover:text-error "/>
            </button>
        </span>
        <span v-if="calendarFilters.adjoiningNotLoud"
              class="flex rounded-full items-center font-medium text-tagText
              border bg-tagBg border-tag px-2 py-1 mt-1 text-sm mr-1 mb-1">
            ohne laute Nebenveranstaltung
            <button @click="calendarFilters.adjoiningNotLoud = !calendarFilters.adjoiningNotLoud; eventAttributes.adjoiningNotLoud.checked = false" type="button">
                <XIcon class="ml-1 h-4 w-4 hover:text-error "/>
            </button>
        </span>
        <span v-if="calendarFilters.showAdjoiningRooms"
              class="flex rounded-full items-center font-medium text-tagText
              border bg-tagBg border-tag px-2 py-1 mt-1 text-sm mr-1 mb-1">
            mit Nebenräumen
            <button @click="calendarFilters.showAdjoiningRooms = !calendarFilters.showAdjoiningRooms; eventAttributes.showAdjoiningRooms.checked = false" type="button">
                <XIcon class="ml-1 h-4 w-4 hover:text-error "/>
            </button>
        </span>
        <span v-if="calendarFilters.allDayFree"
              class="flex rounded-full items-center font-medium text-tagText
              border bg-tagBg border-tag px-2 py-1 mt-1 text-sm mr-1 mb-1">
            ganztägig frei
            <button @click="calendarFilters.allDayFree = !calendarFilters.allDayFree; eventAttributes.allDayFree.checked = false" type="button">
                <XIcon class="ml-1 h-4 w-4 hover:text-error "/>
            </button>
        </span>
    </div>
</template>

<script>

import {
    XIcon,
} from '@heroicons/vue/outline';

export default {
    name: "CalendarFilterTagComponent",
    components: {
        XIcon
    },
    props: {
        calendarFilters: Object,
        eventAttributes: Object
    }
}
</script>

<style scoped>

</style>
