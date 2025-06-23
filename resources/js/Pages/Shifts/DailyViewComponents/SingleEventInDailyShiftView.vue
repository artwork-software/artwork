<template>
    <div class="grid grid-cols-1 md:grid-cols-2 w-full select-none cursor-pointer rounded-lg"
        :style="{ backgroundColor: hexColor + '40' }"
        @click="showEventDetails = !showEventDetails">
        <div class="flex items-center gap-x-2 font-lexend">
            <div class="py-1.5 px-3 min-w-20 rounded-l-lg" :style="{ backgroundColor: hexColor + '90' }">
                <span v-if="event.allDay">{{ $t('All day') }}</span>
                <span v-else>{{ event.formattedDates.startTime }} - {{ event.formattedDates.endTime }}</span>
            </div>
            <div class="text-gray-700 font-semibold">
                {{ event.eventType.abbreviation }}: {{ event.eventName }}
            </div>
        </div>
        <div class="flex items-center justify-end px-3">
            <component
                is="IconChevronDown"
                class="size-5 text-gray-500 hover:text-gray-700 transition-all duration-150 ease-in-out"
                :class="{ 'rotate-180': showEventDetails }"
            />
        </div>
    </div>

    <div v-if="showEventDetails" class="mt-1 ml-4">
        <div v-if="event.timelines !== 0" class="space-y-1">
            <div v-for="(timeline, index) in event.timelines"
                :key="timeline.id"
                class="flex items-center gap-x-2 font-lexend rounded-lg"
                :style="{ backgroundColor: hexColor + '20' }">
                <div class="py-1.5 px-3 min-w-28 rounded-l-lg" :style="{ backgroundColor: hexColor + '50' }">
                    <p class="text-xs">
                        <template v-if="timeline.start_date !== timeline.end_date">
                            {{ timeline.formatted_dates.start_date }} {{ timeline.start }} -
                            {{ timeline.formatted_dates.end_date }} {{ timeline.end }}
                        </template>
                        <template v-else-if="timeline.start_or_end && timeline.start === timeline.end">
                            {{ $t('From') }} {{ timeline.start }}
                        </template>
                        <template v-else-if="!timeline.start_or_end && timeline.start === timeline.end">
                            {{ $t('Until') }} {{ timeline.end }}
                        </template>
                        <template v-else>
                            {{ timeline.start }} - {{ timeline.end }}
                        </template>
                    </p>
                </div>
                <p class="text-xs" v-html="timeline.description"></p>
            </div>
        </div>

        <div class="w-full bg-gray-100 rounded-lg py-1.5 px-3 mt-1">
            <div class="text-artwork-buttons-create cursor-pointer flex items-center gap-x-1"
                @click="showAddTimeLineModal = true">
                <component is="IconWand" class="size-4"/>
                {{ event.timelines.length === 0 ? $t('Create new timeline') : $t('Edit timeline') }}
            </div>
        </div>
    </div>

    <AddEditTimelineModal
        v-if="showAddTimeLineModal"
        :event="event"
        :timelineToEdit="event.timelines"
        @close="showAddTimeLineModal = false"/>
</template>

<script setup>

import {computed, defineAsyncComponent, ref} from "vue";
import UserPopoverTooltip from "@/Layouts/Components/UserPopoverTooltip.vue";

const props = defineProps({
    event: {
        type: Object,
        required: true,
        default: () => ({})
    },
})

const showEventDetails = ref(false);
const showAddTimeLineModal = ref(false);
const hexColor = computed(() => props.event.eventType.hex_code || '#cccccc');
const AddEditTimelineModal = defineAsyncComponent({
    loader: () => import('@/Pages/Projects/Components/TimelineComponents/AddEditTimelineModal.vue'),
    delay: 200,
    timeout: 5000
})
</script>

<style scoped>

</style>