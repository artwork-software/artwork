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


            <BaseInput
                id="event_time_length_minutes"
                label="Default event duration (minutes)"
                type="number"
                class="mt-6"
                v-model="event_time_length_minutes"
                @focusout="update"
            />
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

const update = () => {
    console.log(event_time_length_minutes.value)
    router.patch(route('event.standard.values.update'), {
        event_time_length_minutes: event_time_length_minutes.value,
    }, {
        preserveState: true,
        preserveScroll: true,
    })
}
</script>
<style scoped>

</style>
