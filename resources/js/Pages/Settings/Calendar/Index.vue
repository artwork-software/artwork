<template>
    <AppLayout title="Calendar">
        <div class="artwork-container">
            <div class="">
                <h2 class="headline1 my-6">{{ $t('Calendar Settings') }}</h2>
                <div class="xsLight">
                    {{ $t('Define global settings for the calendar.') }}
                </div>
            </div>

            <div class="my-10 card white p-5">


                <div>
                    <TinyPageHeadline
                        :title="$t('Daily View Calendar Settings')"
                        :description="$t('Specify the times that are to be displayed in a reduced or compressed form in the daily view. With this setting, you determine which time intervals are highlighted less to improve the clarity of the daily view. Specify the exact time period you want so that the hours in your daily calendar are displayed in a clear and structured way.')" />
                </div>

                <div class="my-5">
                   <VisualFeedback :text="visualFeedbackText" :showSaveSuccess="showVisualFeedback" :background-color="visualFeedbackBackgroundColor" />

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 max-w-2xl mt-5">
                        <div>
                            <BaseInput type="time"
                                id="start"
                                v-model="tinyTimePeriod.start"
                                :label="$t('Start-Time')"
                                @focusout="saveTinyCalendarSettings"

                            />
                        </div>
                        <div>
                            <BaseInput type="time"
                                id="end"
                                v-model="tinyTimePeriod.end"
                                :label="$t('End-Time')"
                                @focusout="saveTinyCalendarSettings"
                            />
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>

import AppLayout from "@/Layouts/AppLayout.vue";
import TinyPageHeadline from "@/Components/Headlines/TinyPageHeadline.vue";
import TimeInputComponent from "@/Components/Inputs/TimeInputComponent.vue";
import {useForm, usePage} from "@inertiajs/vue3";
import {ref} from "vue";
import VisualFeedback from "@/Components/Feedback/VisualFeedback.vue";
import BaseInput from "@/Artwork/Inputs/BaseInput.vue";

const props = defineProps({
    calendarSettings: {
        type: Object,
        required: true
    }
})

const tinyTimePeriod = useForm({
    start: props.calendarSettings ? props.calendarSettings.start : '00:00',
    end: props.calendarSettings ? props.calendarSettings.end : '08:00',
})

const showVisualFeedback = ref(false)
const visualFeedbackText = ref('')
const visualFeedbackBackgroundColor = ref('bg-green-600')
const saveTinyCalendarSettings = () => {
    if(!tinyTimePeriod.isDirty) {
        return
    }


    /*if ( tinyTimePeriod.end < tinyTimePeriod.start ) {
        visualFeedbackText.value = 'The end time must be greater than the start time.'
        visualFeedbackBackgroundColor.value = 'bg-red-600'
        showVisualFeedback.value = true
        setTimeout(() => {
            showVisualFeedback.value = false
            visualFeedbackBackgroundColor.value = 'bg-green-600'
            visualFeedbackText.value = ''
        }, 3000)
        return
    }*/


    tinyTimePeriod.post(route('calendar-settings.store'),{
        preserveScroll: true,
        onSuccess: () => {
            visualFeedbackText.value = 'Saved. The changes have been successfully applied.'
            visualFeedbackBackgroundColor.value = 'bg-green-600'
            showVisualFeedback.value = true
            setTimeout(() => {
                showVisualFeedback.value = false
                visualFeedbackBackgroundColor.value = 'bg-green-600'
                visualFeedbackText.value = ''
            }, 3000)
        },
        onError: () => {
            console.log('error')
        }
    })
}

</script>

<style scoped>

</style>
