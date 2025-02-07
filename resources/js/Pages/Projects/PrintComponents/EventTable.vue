<template>
    <div>
        <div>
            <h3 class="xsDark">{{ $t('Events') }}</h3>
        </div>
        <div class="xsDark">
            <div class="">
                <table class="min-w-full divide-y divide-gray-300">
                    <thead>
                    <tr>
                        <th  v-if="usePage().props.event_status_module" scope="col" class="py-3.5 pl-4 pr-3 text-left text-sm font-semibold text-gray-900 sm:pl-0">{{ $t('Event Status') }}</th>
                        <th scope="col" colspan="1" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">{{ $t('Event type')}}</th>
                        <th scope="col" colspan="1" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">{{ $t('Event name') }}</th>
                        <th scope="col" colspan="1" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">{{ $t('Room') }}</th>
                        <th scope="col" colspan="1" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">{{ $t('Day') }}</th>
                        <th scope="col" colspan="1" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">{{ $t('Period') }}</th>
                    </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                    <tr v-for="event in project.events" :key="event.id">
                        <td  v-if="usePage().props.event_status_module" colspan="1" class="whitespace-nowrap py-4 pl-4 pr-3 text-sm font-medium text-gray-900 sm:pl-0">
                            <div class="flex items-center gap-x-2">
                                <div>
                                    <div class="block w-5 h-5 rounded-full"
                                         :style="{'backgroundColor' : event.status?.color }"/>
                                </div>
                                <div class="truncate print:w-full">
                                    {{ event.status?.name }}
                                </div>
                            </div>
                        </td>
                        <td colspan="1" class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">{{ event.eventTypeName }}</td>
                        <td colspan="1" class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">{{ event.alwaysEventName }}</td>
                        <td colspan="1" class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">{{ event.roomName }}</td>
                        <td colspan="1" class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">{{ event.event_date_without_time.start }}</td>
                        <td colspan="1" class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">{{ event.times_without_dates.start }} - {{ event.times_without_dates.end }}</td>
                    </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</template>

<script setup>

import {usePage} from "@inertiajs/vue3";

const props = defineProps({
    project: {
        type: Object,
        required: true
    },
})

</script>

<style scoped>

</style>