<template>
    <AppLayout :title="$t('Standard values')">
        <EventSettingHeader>
            <template #actions>

            </template>
            <SettingsGuideBanner
                class="mb-6"
                storage-key="settings-guide.event.standard-values"
                title="How does this area work?"
                :paragraphs="[
                    'Everything on this tab is only a default: the values pre-fill the form when an event is created and can be changed there at any time.',
                    'Existing events are not affected. Changes here are saved automatically.',
                ]"
            />
            <div>
                <BasePageTitle
                    title="Standard values"
                    :description="$t('Manage default settings for your events to streamline scheduling and ensure consistency across your organization.')"
                />
            </div>

            <div class="mt-6">
                <h3 class="text-sm font-semibold text-text-subtle">{{ $t('Default event duration') }}</h3>
                <p class="text-xs text-text-subtle mt-1">{{ $t('The default event duration specified here will be preselected when you create an event in artwork.') }}</p>
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
                <h3 class="text-sm font-semibold text-text-subtle">{{ $t('Default start time') }}</h3>
                <p class="text-xs text-text-subtle mt-1">{{ $t('The default start time determines which time should be prefilled when you create a new event in artwork.') }}</p>
                <BaseInput
                    id="event_start_time"
                    :label="$t('Default start time')"
                    type="time"
                    class="mt-2"
                    v-model="event_start_time"
                    @focusout="update"
                />
            </div>

            <div class="mt-6">
                <h3 class="text-sm font-semibold text-text-subtle">{{ $t('Full day as default') }}</h3>
                <p class="text-xs text-text-subtle mt-1">{{ $t('If activated, the Full day checkbox is preselected when creating a new event.') }}</p>
                <label class="inline-flex items-center gap-2 mt-2 cursor-pointer">
                    <input
                        type="checkbox"
                        v-model="event_all_day_default"
                        class="ui-checkbox"
                        @change="update"
                    />
                    <span class="text-sm text-text-subtle">{{ $t('Full day') }}</span>
                </label>
            </div>

            <!-- Einlass: Instanz-Schalter (kein Standardwert, aktiviert das Feld) -->
            <div class="mt-10 border-t border-border-subtle pt-8">
                <h3 class="text-sm font-semibold text-text-subtle">{{ $t('Admission') }}</h3>
                <p class="text-xs text-text-subtle mt-1">{{ $t('Would you like to use the ‘Admission’ field for events in {0}?', [usePage().props.page_title]) }}</p>
                <div class="flex items-center gap-x-2 mt-3">
                    <Switch v-model="enable_admission" :class="[enable_admission ? 'bg-accent-600' : 'bg-border-subtle', 'relative inline-flex h-6 w-11 shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-accent-600 focus:ring-offset-2']">
                        <span :class="[enable_admission ? 'translate-x-5' : 'translate-x-0', 'pointer-events-none relative inline-block size-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out']">
                          <span :class="[enable_admission ? 'opacity-0 duration-100 ease-out' : 'opacity-100 duration-200 ease-in', 'absolute inset-0 flex size-full items-center justify-center transition-opacity']" aria-hidden="true">
                            <svg class="size-3 text-text-subtle" fill="none" viewBox="0 0 12 12">
                              <path d="M4 8l2-2m0 0l2-2M6 6L4 4m2 2l2 2" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                            </svg>
                          </span>
                          <span :class="[enable_admission ? 'opacity-100 duration-200 ease-in' : 'opacity-0 duration-100 ease-out', 'absolute inset-0 flex size-full items-center justify-center transition-opacity']" aria-hidden="true">
                            <svg class="size-3 text-accent-600" fill="currentColor" viewBox="0 0 12 12">
                              <path d="M3.707 5.293a1 1 0 00-1.414 1.414l1.414-1.414zM5 8l-.707.707a1 1 0 001.414 0L5 8zm4.707-3.293a1 1 0 00-1.414-1.414l1.414 1.414zm-7.414 2l2 2 1.414-1.414-2-2-1.414 1.414zm3.414 2l4-4-1.414-1.414-4 4 1.414 1.414z" />
                            </svg>
                          </span>
                        </span>
                    </Switch>
                    <span class="text-sm text-text-subtle">{{ $t('Admission') }}</span>
                </div>

                <SettingsGuideBanner
                    class="mt-4"
                    variant="static"
                    title="Where the admission time appears"
                    :paragraphs="[
                        'When the field is active, an optional admission time can be entered in the event creation form and in bulk editing. It refers to the start day of the event.',
                        'The admission time is shown on event tiles in the calendar and the shift plan — users can hide it via the calendar display settings.',
                    ]"
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
import SettingsGuideBanner from "@/Artwork/Guide/SettingsGuideBanner.vue";
import {Switch} from "@headlessui/vue";
import {ref, watch} from "vue";
import {router, usePage} from "@inertiajs/vue3";


const event_time_length_minutes = ref(usePage().props.event_time_length_minutes || 60)
const event_start_time = ref(usePage().props.event_start_time || '09:00')
const event_all_day_default = ref(usePage().props.event_all_day_default || false)
const enable_admission = ref(usePage().props.event_admission_module || false)

const update = () => {
    if (!event_start_time.value) {
        event_start_time.value = '09:00'
    }
    router.patch(route('event.standard.values.update'), {
        event_time_length_minutes: event_time_length_minutes.value,
        event_start_time: event_start_time.value,
        event_all_day_default: event_all_day_default.value,
        enable_admission: enable_admission.value,
    }, {
        preserveState: true,
        preserveScroll: true,
    })
}

// Switch hat kein @change-Event wie native Inputs — Auto-Save via Watcher
watch(enable_admission, () => update())
</script>
<style scoped>

</style>
