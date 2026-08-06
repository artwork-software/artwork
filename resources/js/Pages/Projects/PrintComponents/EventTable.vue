<template>
    <div>
        <div>
            <h3 class="text-sm/5 font-semibold text-text">{{ $t('Events') }}</h3>
        </div>
        <div class="text-sm/5 font-semibold text-text">
            <div class="">
                <table class="min-w-full divide-y divide-border">
                    <thead>
                    <tr>
                        <th  v-if="usePage().props.event_status_module" scope="col" class="py-3.5 pl-4 pr-3 text-left text-sm font-semibold text-text sm:pl-0">{{ $t('Event Status') }}</th>
                        <th scope="col" colspan="1" class="px-3 py-3.5 text-left text-sm font-semibold text-text">{{ $t('Event type')}}</th>
                        <th scope="col" colspan="1" class="px-3 py-3.5 text-left text-sm font-semibold text-text">{{ $t('Event name') }}</th>
                        <th scope="col" colspan="1" class="px-3 py-3.5 text-left text-sm font-semibold text-text">{{ $t('Room') }}</th>
                        <th scope="col" colspan="1" class="px-3 py-3.5 text-left text-sm font-semibold text-text">{{ $t('Day') }}</th>
                        <th scope="col" colspan="1" class="px-3 py-3.5 text-left text-sm font-semibold text-text">{{ $t('Period') }}</th>
                    </tr>
                    </thead>
                    <tbody class="divide-y divide-border-subtle">
                    <tr v-for="event in project.events" :key="event.id">
                        <td  v-if="usePage().props.event_status_module" colspan="1" class="whitespace-nowrap py-4 pl-4 pr-3 text-sm font-medium text-text sm:pl-0">
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
                        <td colspan="1" class="whitespace-nowrap px-3 py-4 text-sm text-text-subtle">{{ event.eventTypeName }}</td>
                        <td colspan="1" class="whitespace-nowrap px-3 py-4 text-sm text-text-subtle">{{ event.alwaysEventName }}</td>
                        <td colspan="1" class="whitespace-nowrap px-3 py-4 text-sm text-text-subtle">{{ event.roomName }}</td>
                        <td colspan="1" class="whitespace-nowrap px-3 py-4 text-sm text-text-subtle">{{ event.event_date_without_time.start }}</td>
                        <td colspan="1" class="whitespace-nowrap px-3 py-4 text-sm text-text-subtle">{{ event.times_without_dates.start }} - {{ event.times_without_dates.end }}</td>
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