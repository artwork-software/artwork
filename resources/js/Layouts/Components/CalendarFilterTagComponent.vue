<template>
    <div class="my-1 flex flex-wrap">
        <span v-for="attribute in calendarFilters.roomAttributes"
              class="flex rounded-full items-center font-medium text-tagText
              border bg-tagBg border-tag px-2 py-1 mt-1 text-sm mr-1 mb-1">
            {{ attribute.name }}
            <button
                @click="attribute.checked = false; this.$parent.changeFilterElements(calendarFilters.roomAttributes,'roomAttributes', attribute);"
                type="button">
                <IconX stroke-width="1.5" class="ml-1 h-4 w-4 hover:text-error "/>
            </button>
        </span>
        <span v-for="category in calendarFilters.roomCategories"
              class="flex rounded-full items-center font-medium text-tagText
              border bg-tagBg border-tag px-2 py-1 mt-1 text-sm mr-1 mb-1">
            {{ category.name }}
            <button
                @click="category.checked = false; this.$parent.changeFilterElements(calendarFilters.roomCategories,'roomCategories', category);"
                type="button">
                <IconX stroke-width="1.5" class="ml-1 h-4 w-4 hover:text-error "/>
            </button>
        </span>
        <span v-for="room in calendarFilters.rooms"
              class="flex rounded-full items-center font-medium text-tagText
              border bg-tagBg border-tag px-2 py-1 mt-1 text-sm mr-1 mb-1">
            {{ room.label || room.name }}
            <button @click="room.checked = false; this.$parent.changeFilterElements(calendarFilters.rooms,'rooms', room)"
                    type="button">
                <IconX stroke-width="1.5" class="ml-1 h-4 w-4 hover:text-error "/>
            </button>
        </span>
        <span v-for="area in calendarFilters.areas"
              class="flex rounded-full items-center font-medium text-tagText
              border bg-tagBg border-tag px-2 py-1 mt-1 text-sm mr-1 mb-1">
            {{ area.label || area.name }}
            <button @click="area.checked = false; this.$parent.changeFilterElements(calendarFilters.areas,'areas', area);"
                    type="button">
                <IconX stroke-width="1.5" class="ml-1 h-4 w-4 hover:text-error "/>
            </button>
        </span>
        <span v-for="eventType in calendarFilters.eventTypes"
              class="flex rounded-full items-center font-medium text-tagText
              border bg-tagBg border-tag px-2 py-1 mt-1 text-sm mr-1 mb-1">
            {{ eventType.name }}
            <button
                @click="eventType.checked = false; this.$parent.changeFilterElements(calendarFilters.eventTypes, 'eventTypes', eventType)"
                type="button">
                <XIcon class="ml-1 h-4 w-4 hover:text-error "/>
            </button>
        </span>
        <span v-if="calendarFilters.hasAudience"
              class="flex rounded-full items-center font-medium text-tagText
              border bg-tagBg border-tag px-2 py-1 mt-1 text-sm mr-1 mb-1">
            {{ $t('with audience')}}
            <button @click="calendarFilters.hasAudience = !calendarFilters.hasAudience; eventAttributes.hasAudience.checked = false; this.updateDisplayedEvents()" type="button">
                <IconX stroke-width="1.5" class="ml-1 h-4 w-4 hover:text-error "/>
            </button>
        </span>
        <span v-if="calendarFilters.hasNoAudience"
              class="flex rounded-full items-center font-medium text-tagText
              border bg-tagBg border-tag px-2 py-1 mt-1 text-sm mr-1 mb-1">
            {{ $t('without audience')}}
            <button @click="calendarFilters.hasNoAudience = !calendarFilters.hasNoAudience; eventAttributes.hasNoAudience.checked = false; this.updateDisplayedEvents()" type="button">
                <XIcon class="ml-1 h-4 w-4 hover:text-error "/>
            </button>
        </span>
        <span v-if="calendarFilters.isLoud"
              class="flex rounded-full items-center font-medium text-tagText
              border bg-tagBg border-tag px-2 py-1 mt-1 text-sm mr-1 mb-1">
            {{  $t('loud') }}
            <button @click="calendarFilters.isLoud = !calendarFilters.isLoud; eventAttributes.isLoud.checked = false; this.updateDisplayedEvents()" type="button">
                <IconX stroke-width="1.5" class="ml-1 h-4 w-4 hover:text-error "/>
            </button>
        </span>
        <span v-if="calendarFilters.isNotLoud"
              class="flex rounded-full items-center font-medium text-tagText
              border bg-tagBg border-tag px-2 py-1 mt-1 text-sm mr-1 mb-1">
            {{  $t('not loud') }}
            <button @click="calendarFilters.isNotLoud = !calendarFilters.isNotLoud; eventAttributes.isNotLoud.checked = false; this.updateDisplayedEvents()" type="button">
                <IconX stroke-width="1.5" class="ml-1 h-4 w-4 hover:text-error "/>
            </button>
        </span>
        <span v-if="calendarFilters.adjoiningNoAudience"
              class="flex rounded-full items-center font-medium text-tagText
              border bg-tagBg border-tag px-2 py-1 mt-1 text-sm mr-1 mb-1">
             {{  $t('without side event with audience') }}
            <button @click="calendarFilters.adjoiningNoAudience = !calendarFilters.adjoiningNoAudience; eventAttributes.adjoiningNoAudience.checked = false; this.updateDisplayedEvents()" type="button">
                <IconX stroke-width="1.5" class="ml-1 h-4 w-4 hover:text-error "/>
            </button>
        </span>
        <span v-if="calendarFilters.adjoiningNotLoud"
              class="flex rounded-full items-center font-medium text-tagText
              border bg-tagBg border-tag px-2 py-1 mt-1 text-sm mr-1 mb-1">
             {{  $t('without a loud side event') }}
            <button @click="calendarFilters.adjoiningNotLoud = !calendarFilters.adjoiningNotLoud; eventAttributes.adjoiningNotLoud.checked = false; this.updateDisplayedEvents()" type="button">
                <IconX stroke-width="1.5" class="ml-1 h-4 w-4 hover:text-error "/>
            </button>
        </span>
        <span v-if="calendarFilters.showAdjoiningRooms"
              class="flex rounded-full items-center font-medium text-tagText
              border bg-tagBg border-tag px-2 py-1 mt-1 text-sm mr-1 mb-1">
             {{  $t('with adjoining rooms') }}
            <button @click="calendarFilters.showAdjoiningRooms = !calendarFilters.showAdjoiningRooms; roomFilters.showAdjoiningRooms = false; this.updateDisplayedEvents()" type="button">
                <IconX stroke-width="1.5" class="ml-1 h-4 w-4 hover:text-error "/>
            </button>
        </span>
        <span v-if="calendarFilters.allDayFree"
              class="flex rounded-full items-center font-medium text-tagText
              border bg-tagBg border-tag px-2 py-1 mt-1 text-sm mr-1 mb-1">
             {{  $t('free all day') }}
            <button @click="calendarFilters.allDayFree = !calendarFilters.allDayFree; roomFilters.allDayFree = false; this.updateDisplayedEvents()" type="button">
                <IconX stroke-width="1.5" class="ml-1 h-4 w-4 hover:text-error "/>
            </button>
        </span>
    </div>
</template>

<script>

import {
    XIcon,
} from '@heroicons/vue/outline';
import Permissions from "@/Mixins/Permissions.vue";
import IconLib from "@/Mixins/IconLib.vue";

export default {
    name: "CalendarFilterTagComponent",
    mixins: [Permissions, IconLib],
    components: {
        XIcon
    },
    props: {
        calendarFilters: Object,
        eventAttributes: Object,
        roomFilters: Object,
        eventsSince: Date,
        eventsUntil: Date
    },
    methods: {
        updateDisplayedEvents() {
            this.$parent.fetchEvents({startDate: this.eventsSince, endDate: this.eventsUntil})
        },
    }
}
</script>

<style scoped>

</style>
