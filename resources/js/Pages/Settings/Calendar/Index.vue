<template>
    <CalendarSettingsHeader :title="$t('Calendar Settings')" :description="$t('Define global settings for the calendar.')">
        <SettingsGuideBanner
            class="mb-5"
            storage-key="settings-guide.calendar.general"
            title="How does this area work?"
            :paragraphs="[
                'Start and end time define which hours the day view of the calendar and the shift plan displays. The range may span midnight — for example 22:00 to 08:00.',
                'Day remarks add one shared remark per day as a separate column in the calendar, the planning calendar and the shift plan.',
            ]"
        />
        <div class="card white p-5">
            <div>
                <BasePageTitle
                    :title="$t('Calendar Settings')"
                    :description="$t('Defines which hours the day view of the calendar and the shift plan displays. The range may span midnight (e.g. 22:00–08:00).')" />
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

        <div class="card white p-5 mt-5">
            <BasePageTitle
                :title="$t('Day remarks')"
                :description="$t('If activated, the calendar, planning calendar and shift plan show a separate day remarks column with one shared remark per day. Who can see and edit the remarks is controlled via the user permissions.')" />

            <SwitchGroup as="div" class="flex flex-row items-center gap-x-2 cursor-pointer mt-4">
                <SwitchLabel as="span" class="text-sm">
                    <span :class="[!tinyTimePeriod.day_remarks_enabled ? 'font-bold' : 'font-medium', 'text-gray-900']">
                        {{ $t('Deactivated') }}
                    </span>
                </SwitchLabel>
                <Switch v-model="tinyTimePeriod.day_remarks_enabled"
                        @update:model-value="saveDayRemarksSettings"
                        :class="[
                            tinyTimePeriod.day_remarks_enabled ?
                                'bg-artwork-buttons-create' :
                                'bg-gray-200',
                            'relative inline-flex h-3 w-6 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-indigo-600 focus:ring-offset-2'
                        ]">
                    <span aria-hidden="true" :class="[tinyTimePeriod.day_remarks_enabled ? 'translate-x-3' : 'translate-x-0', 'pointer-events-none inline-block h-2 w-2 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out']" />
                </Switch>
                <SwitchLabel as="span" class="text-sm">
                    <span :class="[tinyTimePeriod.day_remarks_enabled ? 'font-bold' : 'font-medium', 'text-gray-900']">
                        {{ $t('Activate day remarks column') }}
                    </span>
                </SwitchLabel>
            </SwitchGroup>

            <fieldset v-if="tinyTimePeriod.day_remarks_enabled" class="mt-5">
                <legend class="text-sm font-semibold text-gray-900">{{ $t('Visibility of the column') }}</legend>
                <div class="mt-2 space-y-3">
                    <label class="flex items-start gap-x-2 cursor-pointer">
                        <input type="radio"
                               name="day_remarks_mandatory"
                               :value="false"
                               v-model="tinyTimePeriod.day_remarks_mandatory"
                               @change="saveDayRemarksSettings"
                               class="mt-0.5 h-4 w-4 border-gray-300 text-artwork-buttons-create focus:ring-artwork-buttons-create"/>
                        <span class="text-sm text-gray-900">
                            <span class="font-medium">{{ $t('Users decide themselves') }}</span>
                            <span class="block text-xs text-gray-500">{{ $t('Users can show or hide the column via their display settings. It is shown by default.') }}</span>
                        </span>
                    </label>
                    <label class="flex items-start gap-x-2 cursor-pointer">
                        <input type="radio"
                               name="day_remarks_mandatory"
                               :value="true"
                               v-model="tinyTimePeriod.day_remarks_mandatory"
                               @change="saveDayRemarksSettings"
                               class="mt-0.5 h-4 w-4 border-gray-300 text-artwork-buttons-create focus:ring-artwork-buttons-create"/>
                        <span class="text-sm text-gray-900">
                            <span class="font-medium">{{ $t('Column is mandatory for all users') }}</span>
                            <span class="block text-xs text-gray-500">{{ $t('The column is always shown and cannot be hidden via the display settings.') }}</span>
                        </span>
                    </label>
                </div>
            </fieldset>
        </div>
    </CalendarSettingsHeader>
</template>

<script setup>

import CalendarSettingsHeader from "@/Pages/Settings/Components/CalendarSettingsHeader.vue";
import TinyPageHeadline from "@/Components/Headlines/TinyPageHeadline.vue";
import TimeInputComponent from "@/Components/Inputs/TimeInputComponent.vue";
import {useForm, usePage} from "@inertiajs/vue3";
import {ref} from "vue";
import VisualFeedback from "@/Components/Feedback/VisualFeedback.vue";
import BaseInput from "@/Artwork/Inputs/BaseInput.vue";
import BasePageTitle from "@/Artwork/Titles/BasePageTitle.vue";
import SettingsGuideBanner from "@/Artwork/Guide/SettingsGuideBanner.vue";
import {Switch, SwitchGroup, SwitchLabel} from "@headlessui/vue";

const props = defineProps({
    calendarSettings: {
        type: Object,
        required: true
    }
})

const tinyTimePeriod = useForm({
    start: props.calendarSettings ? props.calendarSettings.start : '00:00',
    end: props.calendarSettings ? props.calendarSettings.end : '08:00',
    day_remarks_enabled: props.calendarSettings?.day_remarks_enabled ?? false,
    day_remarks_mandatory: props.calendarSettings?.day_remarks_mandatory ?? false,
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


    postCalendarSettings()
}

// Toggle/Radio speichern sofort — isDirty-Check entfällt, da @update:model-value
// bereits nur bei echter Änderung feuert
const saveDayRemarksSettings = () => {
    postCalendarSettings()
}

const postCalendarSettings = () => {
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
