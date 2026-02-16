<template>
    <AppLayout :title="$t('Standard values')">
        <EventSettingHeader>
            <template #actions>

            </template>
            <div>
                <BasePageTitle
                    title="Standard values"
                    :description="$t('Manage default settings for your events to streamline scheduling and ensure consistency across your organization.')"
                />
            </div>

            <div class="mt-6">
                <h3 class="text-sm font-semibold text-secondary">{{ $t('Default event duration') }}</h3>
                <p class="text-xs text-secondary mt-1">{{ $t('The default event duration specified here will be preselected when you create an event in artwork.') }}</p>
                <BaseInput
                    id="event_time_length_minutes"
                    :label="$t('Default event duration (minutes)')"
                    type="number"
                    class="mt-2"
                    v-model="event_time_length_minutes"
                    @focusout="update"
                />
            </div>

            <div class="mt-6">
                <h3 class="text-sm font-semibold text-secondary">{{ $t('Default start time') }}</h3>
                <p class="text-xs text-secondary mt-1">{{ $t('The default start time determines which time should be prefilled when you create a new event in artwork.') }}</p>
                <BaseInput
                    id="event_start_time"
                    :label="$t('Default start time')"
                    type="time"
                    class="mt-2"
                    v-model="event_start_time"
                    @focusout="update"
                />
            </div>
        </EventSettingHeader>
    </AppLayout>
</template>

<script setup>

import EventSettingHeader from "@/Pages/Settings/EventSettingComponents/EventSettingHeader.vue";
import BasePageTitle from "@/Artwork/Titles/BasePageTitle.vue";
import AppLayout from "@/Layouts/AppLayout.vue";
import BaseInput from "@/Artwork/Inputs/BaseInput.vue";
import {ref} from "vue";
import {router, usePage} from "@inertiajs/vue3";


const event_time_length_minutes = ref(usePage().props.event_time_length_minutes || 60)
const event_start_time = ref(usePage().props.event_start_time || '09:00')

const update = () => {
    if (!event_start_time.value) {
        event_start_time.value = '09:00'
    }
    router.patch(route('event.standard.values.update'), {
        event_time_length_minutes: event_time_length_minutes.value,
        event_start_time: event_start_time.value,
    }, {
        preserveState: true,
        preserveScroll: true,
    })
}
</script>
<style scoped>

</style>
