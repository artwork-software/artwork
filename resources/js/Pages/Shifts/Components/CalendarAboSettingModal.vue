<script setup>
import { ref, computed } from 'vue'
import { useForm, usePage } from '@inertiajs/vue3'
import BaseModal from '@/Components/Modals/BaseModal.vue'
import FormButton from '@/Layouts/Components/General/Buttons/FormButton.vue'
import CalendarAboInfoModal from '@/Pages/Shifts/Components/CalendarAboInfoModal.vue'
import BaseInput from "@/Artwork/Inputs/BaseInput.vue";
import ArtworkBaseListbox from "@/Artwork/Listbox/ArtworkBaseListbox.vue";
import ArtworkBaseToggle from "@/Artwork/Toggles/ArtworkBaseToggle.vue";
import {IconInfoCircle} from "@tabler/icons-vue";
import ArtworkBaseModal from "@/Artwork/Modals/ArtworkBaseModal.vue";
import BaseUIButton from "@/Artwork/Buttons/BaseUIButton.vue";

// Props & Emits
const props = defineProps({
    eventTypes: { type: Array, required: true }
})
const emit = defineEmits(['close'])

// Page / User
const page = usePage()
const user = computed(() => page?.props?.auth?.user ?? {})

// Vorbelegung aus User.abo
const abo = user.value?.shift_calendar_abo ?? null

// Formular
const aboForm = useForm({
    id: abo ? abo.id : null,
    date_range: abo ? !!abo.date_range : false,
    start_date: abo ? abo.start_date : null,
    end_date: abo ? abo.end_date : null,
    specific_event_types: abo ? !!abo.specific_event_types : false,
    event_types: [],
    enable_notification: abo ? !!abo.enable_notification : false,
    notification_time: abo ? (abo.notification_time ?? 0) : 0,
    notification_time_unit: abo ? (abo.notification_time_unit ?? 'minutes') : 'minutes',
})

// Mehrfachauswahl der Typen als Objekte (für Listbox)
const selectedEventTypes = ref(
    abo ? props.eventTypes.filter(et => (abo.event_types ?? []).includes(et.id)) : []
)

const showCalendarAboInfoModal = ref(false)

// Helpers
function closeModal(success = false) {
    emit('close', success)
}

function create() {
    // Mappe selektierte Typen auf IDs für das Backend
    aboForm.event_types = (selectedEventTypes.value ?? []).map(et => et.id)

    if (aboForm.id) {
        aboForm.patch(route('user.shift.calendar.abo.update', aboForm.id), {
            preserveScroll: true,
            preserveState: true,
            onSuccess: () => closeModal(true),
        })
    } else {
        aboForm.post(route('user.shift.calendar.abo.create'), {
            preserveScroll: true,
            preserveState: true,
            onSuccess: () => closeModal(true),
        })
    }
}

// UI-Text als computed, falls Übersetzungen erst später kommen
const selectedEventTypesLabel = computed(() =>
    (selectedEventTypes.value ?? []).map(et => et?.name).join(', ') || page.props.ziggy?.locale ? '' : ''
)
</script>

<template>
    <ArtworkBaseModal
        v-if="true"
        @close="closeModal(false)"
        title="Shift Calendar subscription settings"
        description="Customize your calendar subscription to suit your individual needs. Select a specific time period, certain appointment types and activate notifications to stay optimally informed and make your planning easier. Use these settings to configure your calendar according to your preferences and stay organized."
    >

        <!-- Abschnitt: Zeitraum -->
        <div class="my-5 border-b border-dotted border-gray-200 pb-5">
            <ArtworkBaseToggle
                label="Set period for calendar subscription"
                description="Select a specific period for your calendar subscription. If you do not select a period, the subscription will continue indefinitely. This allows you to subscribe to the calendar for a set period of time only."
                v-model="aboForm.date_range"
                id="date_range"
                name="date_range"
                use-translation
            />

            <div v-if="aboForm.date_range" class="mt-4 ml-0 md:ml-7">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    <BaseInput
                        id="start_date"
                        type="date"
                        v-model="aboForm.start_date"
                        :label="$t('Period Start')"
                    />
                    <BaseInput
                        id="end_date"
                        type="date"
                        v-model="aboForm.end_date"
                        :label="$t('Period End')"
                    />
                </div>
            </div>
        </div>

        <!-- Abschnitt: Event-Typen -->
        <div class="my-5 border-b border-dotted border-gray-200 pb-5">
            <ArtworkBaseToggle
                label="Select event types for calendar subscription"
                description="Select specific types of events for your calendar subscription. This allows you to specify which event types should be displayed in your subscribed calendar to optimize your planning."
                v-model="aboForm.specific_event_types"
                id="specific_event_types"
                name="specific_event_types"
                use-translation
            />


            <div v-if="aboForm.specific_event_types" class="mt-4 ml-0 md:ml-7">
                <ArtworkBaseListbox
                    v-model="selectedEventTypes"
                    :items="props.eventTypes"
                    by="id"
                    multiple
                    option-label="name"
                    option-key="id"
                    :use-translations="false"
                    :label="$t('Select event types')"
                    :placeholder="$t('Select event types')"
                />
            </div>
        </div>

        <!-- Abschnitt: Benachrichtigungen -->
        <div class="my-5">
            <ArtworkBaseToggle
                label="Activate calendar notifications"
                description="Receive timely notifications about upcoming events. Activate this option to receive reminders and alerts for your scheduled events and make sure you don't miss any important events."
                v-model="aboForm.enable_notification"
                id="enable_notification"
                name="enable_notification"
                use-translation
            />

            <div v-if="aboForm.enable_notification" class="mt-4 ml-0 md:ml-7">
                <label class="block text-sm font-medium leading-6 text-gray-900 mb-2">
                    {{ $t('Notification settings') }}
                </label>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                    <div class="sm:col-span-1">
                        <BaseInput
                            id="notification_time"
                            type="number"
                            v-model="aboForm.notification_time"
                            :label="$t('Lead time')"
                            :step="1"
                        />
                    </div>

                    <div class="sm:col-span-2">
                        <!-- Native Select (bewusst kein BaseInput, klar getrennte Kontrolle) -->
                        <div class="relative">
                            <select
                                v-model="aboForm.notification_time_unit"
                                id="notification_time_unit"
                                name="notification_time_unit"
                                class="block w-full rounded-md border border-gray-200 bg-white py-2.5 px-3 h-14 text-sm focus:outline-none focus:ring-1 focus:ring-artwork-buttons-create"
                            >
                                <option value="minutes">{{ $t('Minute(s)') }}</option>
                                <option value="hours">{{ $t('Hour(s)') }}</option>
                                <option value="days">{{ $t('Day(s)') }}</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Footer-Aktionen -->
        <div class="flex items-center justify-between">
            <div>
                <button
                    v-if="aboForm.id"
                    type="button"
                    class="flex items-center text-xs text-artwork-buttons-hover underline"
                    @click="showCalendarAboInfoModal = true"
                >
                    <component :is="IconInfoCircle" class="h-4 w-4" stroke-width="1.5" />
                    <span class="ml-1">{{ $t('Show instructions') }}</span>
                </button>
            </div>

            <BaseUIButton
                @click="create"
                :label="aboForm.id ? $t('Save') : $t('Subscribe')"
                :disabled="aboForm.processing"
                is-add-button
            />
        </div>

        <!-- Info-Hinweis -->
        <div
            v-if="aboForm.id"
            class="mt-3 text-artwork-buttons-create bg-artwork-buttons-create/10 rounded-lg p-3"
        >
            <div class="flex items-center gap-1 mb-2">
                <component :is="IconInfoCircle" class="h-4 w-4" stroke-width="1.5" />
                <h5 class="font-bold text-sm">{{ $t('Information') }}</h5>
            </div>
            <div class="text-xs text-artwork-buttons-create w-fit">
                {{
                    $t(
                        'As soon as you click on “Save”, your subscription will be updated and the settings will be saved. If you have subscribed to the calendar via the link, your entries in the calendar program will be updated automatically. Alternatively, you can also download the ICS file and then insert it into your calendar program.'
                    )
                }}
            </div>
        </div>

        <!-- Info Modal -->
        <CalendarAboInfoModal
            v-if="showCalendarAboInfoModal"
            @close="showCalendarAboInfoModal = false"
            is_shift_calendar_abo
        />
    </ArtworkBaseModal>
</template>

<style scoped>
/* keine zusätzlichen Styles nötig – alles via Utility-Klassen */
</style>
