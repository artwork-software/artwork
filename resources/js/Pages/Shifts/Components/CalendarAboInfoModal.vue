<script setup>
import { ref, computed, onBeforeUnmount } from 'vue'
import { usePage } from '@inertiajs/vue3'
import ArtworkBaseModal from '@/Artwork/Modals/ArtworkBaseModal.vue'
import FormButton from '@/Layouts/Components/General/Buttons/FormButton.vue'
import { Disclosure, DisclosureButton, DisclosurePanel } from '@headlessui/vue'
import { ChevronUpIcon } from '@heroicons/vue/solid'
import {IconChevronDown, IconCircleCheck, IconInfoCircle} from "@tabler/icons-vue";
import BaseUIButton from "@/Artwork/Buttons/BaseUIButton.vue";

// Props / Emits
const props = defineProps({
    is_shift_calendar_abo: { type: Boolean, default: false }
})
const emit = defineEmits(['close'])

// Page/User
const page = usePage()
const user = computed(() => page?.props?.auth?.user ?? {})

// URL ableiten
const calendarUrl = computed(() => {
    const shiftUrl = user.value?.shift_calendar_abo?.calendar_abo_url
    const baseUrl  = user.value?.calendar_abo?.calendar_abo_url
    return props.is_shift_calendar_abo ? (shiftUrl || '') : (baseUrl || '')
})

// Copy-Feedback
const copyText = ref('')
let copyTimer = null
function copyAboUrlToClipboard() {
    if (!navigator.clipboard || !calendarUrl.value) return
    navigator.clipboard.writeText(calendarUrl.value).then(() => {
        copyText.value = 'Copied'
        clearTimeout(copyTimer)
        copyTimer = setTimeout(() => (copyText.value = ''), 2000)
    })
}
onBeforeUnmount(() => clearTimeout(copyTimer))

// Download ICS
function downloadICSFile() {
    if (!calendarUrl.value) return
    const a = document.createElement('a')
    a.href = calendarUrl.value
    a.download = 'Schichtkalender.ics'
    a.click()
}

// Schließen
function closeModal(ok = false) {
    emit('close', ok)
}

// Anleitungen (mit $t zur Laufzeit)
const instructions = computed(() => ([
    {
        system: 'Google Calendar',
        description: 'How to subscribe to a calendar in Google Calendar:',
        steps: [
            'Open Google Calendar.',
            'Click on the plus sign next to “More calendars” and select “By URL”.',
            'Enter the URL (see text box) and click on “Add calendar”.',
        ]
    },
    {
        system: 'Apple Calendar',
        description: 'How to subscribe to a calendar in Apple Calendar:',
        steps: [
            'Open the Calendar app.',
            'Go to "File" > "New Calendar Subscription".',
            'Enter the URL (see text box) and click on "Subscribe".',
        ]
    },
    {
        system: 'Thunderbird Lightning',
        description: 'How to subscribe to a calendar in Thunderbird Lightning:',
        steps: [
            'Open Thunderbird and go to "Calendar".',
            'Right-click on the calendar panel and select "New Calendar".',
            'Select "On the Network" and click "Next".',
            'Select "iCalendar (ICS)" and enter the URL (see text box). Click "Next".',
            'Give the calendar a name and choose a color. Click "Next" and then "Finish".',
        ]
    },
    {
        system: 'Yahoo Calendar',
        description: 'How to subscribe to a calendar in Yahoo Calendar:',
        steps: [
            'Open Yahoo Calendar.',
            'Click on the gear icon and select "Subscribe to Calendar".',
            'Enter the URL (see text box) in the "iCal Address" field and click "Subscribe to Calendar".',
        ]
    },
    {
        system: 'Zoho Calendar',
        description: 'How to subscribe to a calendar in Zoho Calendar:',
        steps: [
            'Open Zoho Calendar.',
            'Click on the plus sign next to "My Calendars" and select "Subscribe to Calendar".',
            'Select "By URL" and enter the URL (see text box). Click "Subscribe".',
        ]
    },
    {
        system: 'Android Calendar',
        description: 'How to subscribe to a calendar in the Android Calendar app:',
        steps: [
            'Open the calendar app on your Android device.',
            'Go to "Settings" and select "Add calendar".',
            'Select "Add by URL" and enter the URL (see text box). Click "Subscribe".',
        ]
    },
    {
        system: 'Outlook.com',
        description: 'How to subscribe to a calendar in Outlook.com:',
        steps: [
            'Open Outlook.com and go to "Calendar".',
            'Click on "Add calendar" and select "From internet".',
            'Enter the URL (see text box) and click on "Import calendar".',
        ]
    }
]).map(i => ({
    system: typeof window?.$t === 'function' ? window.$t(i.system) : i.system,
    description: typeof window?.$t === 'function' ? window.$t(i.description) : i.description,
    steps: i.steps.map(s => (typeof window?.$t === 'function' ? window.$t(s) : s))
})))
</script>

<template>
    <ArtworkBaseModal
        @close="closeModal(true)"
        :title="$t('Instructions for subscribing to the calendar')"
        :description="$t('Here you will find detailed instructions on how to subscribe to the calendar in various applications. Follow the appropriate links for your favorite calendar application to complete the subscription process and stay up to date with your appointments. You can also manually import the calendar into your calendar application by downloading the ICS file.')"
    >
        <div class="flex flex-col gap-8 max-h-[calc(100vh-16rem)] overflow-auto pr-1">
            <!-- Accordion -->
            <section>
                <h3 class="text-sm font-semibold text-gray-900 mb-3">
                    {{ $t('Step-by-step guides') }}
                </h3>

                <div class="space-y-2">
                    <Disclosure
                        v-for="(instruction, idx) in instructions"
                        :key="idx"
                        as="div"
                        class="rounded-lg border border-gray-200 bg-white shadow-sm"
                    >
                        <DisclosureButton
                            class="flex w-full items-center justify-between gap-3 rounded-lg px-4 py-3 text-left"
                        >
                              <span class="font-medium text-gray-900 truncate">
                                {{ $t(instruction.system) }}
                              </span>
                            <component :is="IconChevronDown" class="h-5 w-5 text-gray-500 transition-transform duration-200 ui-open:rotate-180"/>
                        </DisclosureButton>

                        <DisclosurePanel class="px-4 pb-4 pt-2 text-sm">
                            <p class="text-gray-600 mb-3">
                                {{ $t(instruction.description) }}
                            </p>
                            <ul class="list-disc list-inside space-y-1 text-gray-700">
                                <li v-for="(step, sIdx) in instruction.steps" :key="sIdx">
                                    {{ $t(step) }}
                                </li>
                            </ul>
                        </DisclosurePanel>
                    </Disclosure>
                </div>
            </section>

            <!-- URL Kopieren -->
            <section>
                <h3 class="text-sm font-semibold text-gray-900 mb-2">
                    {{ $t('Calendar URL') }}
                </h3>
                <p class="text-sm text-gray-600 mb-2">
                    {{ $t('Use the following URL to import the calendar into your calendar application and stay up to date:') }}
                </p>

                <div class="relative">
                    <input
                        :value="calendarUrl"
                        readonly
                        @click="$event.target.select()"
                        id="calendar-url"
                        name="calendar-url"
                        class="block w-full rounded-md border border-gray-200 bg-gray-50 px-3 py-2 text-sm shadow-sm focus:outline-none focus:ring-1 focus:ring-artwork-buttons-create focus:border-artwork-buttons-create"
                    />
                    <button
                        type="button"
                        class="absolute right-1 top-1 bottom-1 my-auto inline-flex items-center gap-2 rounded-md bg-artwork-buttons-create px-3 py-1.5 text-sm font-medium text-white shadow-sm hover:opacity-90 focus:outline-none focus:ring-2 focus:ring-artwork-buttons-create"
                        @click="copyAboUrlToClipboard"
                    >
                        <component :is="IconCircleCheck" class="h-4 w-4" v-if="copyText" />
                        {{ copyText || $t('Copy') }}
                    </button>
                </div>

                <div class="mt-2 text-xs text-artwork-buttons-create flex items-center gap-1">
                    <component :is="IconInfoCircle" class="h-4 w-4" />
                    {{ $t('Click on “Copy” to copy the URL to your clipboard and paste it into the desired calendar application.') }}
                </div>
            </section>

            <!-- Download + Close -->
            <section class="flex items-start justify-between gap-4">
                <div class="max-w-xl">
                    <p class="text-sm text-gray-600 mb-2">
                        {{ $t('Download the ICS file to manually import the calendar directly into your application:') }}
                    </p>
                    <button
                        type="button"
                        class="text-sm text-artwork-buttons-hover underline"
                        @click="downloadICSFile"
                    >
                        {{ $t('Download ICS file') }}
                    </button>
                    <div class="mt-2 text-xs text-artwork-messages-error">
                        {{ $t('Attention - The ICS file is not updated automatically. You must re-import the file regularly to keep it up to date.') }}
                    </div>
                </div>

                <BaseUIButton @click="closeModal(true)" :label="$t('Close')" is-delete-button icon="IconX" />
            </section>
        </div>
    </ArtworkBaseModal>
</template>

<style scoped>
/* reines Utility-Design, kein zusätzliches CSS nötig */
</style>
