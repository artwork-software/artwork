<script setup>
import { ref, computed } from 'vue'
import { useForm, usePage } from '@inertiajs/vue3'
import BaseModal from '@/Components/Modals/BaseModal.vue'
import FormButton from '@/Layouts/Components/General/Buttons/FormButton.vue'
import CalendarAboInfoModal from '@/Pages/Shifts/Components/CalendarAboInfoModal.vue'
import {
    Switch,
    SwitchGroup,
    SwitchLabel,
    Listbox,
    ListboxButton,
    ListboxLabel,
    ListboxOption,
    ListboxOptions,
} from '@headlessui/vue'
import BaseInput from "@/Artwork/Inputs/BaseInput.vue";

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
    <BaseModal
        v-if="true"
        @closed="closeModal(false)"
        modal-image="/Svgs/Overlays/illu_appointment_new.svg"
    >
        <!-- Header -->
        <div class="mb-5">
            <h2 class="headline1 mb-3">
                {{ $t('Shift Calendar subscription settings') }}
            </h2>
            <p class="text-secondary subpixel-antialiased text-sm">
                {{
                    $t(
                        'Customize your calendar subscription to suit your individual needs. Select a specific time period, certain appointment types and activate notifications to stay optimally informed and make your planning easier. Use these settings to configure your calendar according to your preferences and stay organized.'
                    )
                }}
            </p>
        </div>

        <!-- Abschnitt: Zeitraum -->
        <div class="my-5 border-b border-dotted border-gray-200 pb-5">
            <SwitchGroup as="div" class="flex items-start gap-3">
                <Switch
                    v-model="aboForm.date_range"
                    :class="[
            aboForm.date_range ? 'bg-artwork-buttons-create' : 'bg-gray-200',
            'relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-artwork-buttons-create focus:ring-offset-2'
          ]"
                >
          <span
              aria-hidden="true"
              :class="[
              aboForm.date_range ? 'translate-x-5' : 'translate-x-0',
              'pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out'
            ]"
          />
                </Switch>
                <div class="text-sm leading-6">
                    <SwitchLabel class="font-medium text-gray-900">
                        {{ $t('Set period for calendar subscription') }}
                    </SwitchLabel>
                    <p class="text-gray-500">
                        {{
                            $t(
                                'Select a specific period for your calendar subscription. If you do not select a period, the subscription will continue indefinitely. This allows you to subscribe to the calendar for a set period of time only.'
                            )
                        }}
                    </p>
                </div>
            </SwitchGroup>

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
            <SwitchGroup as="div" class="flex items-start gap-3">
                <Switch
                    v-model="aboForm.specific_event_types"
                    :class="[
            aboForm.specific_event_types ? 'bg-artwork-buttons-create' : 'bg-gray-200',
            'relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-artwork-buttons-create focus:ring-offset-2'
          ]"
                >
          <span
              aria-hidden="true"
              :class="[
              aboForm.specific_event_types ? 'translate-x-5' : 'translate-x-0',
              'pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out'
            ]"
          />
                </Switch>
                <div class="text-sm leading-6">
                    <SwitchLabel class="font-medium text-gray-900">
                        {{ $t('Select event types for calendar subscription') }}
                    </SwitchLabel>
                    <p class="text-gray-500">
                        {{
                            $t(
                                'Select specific types of events for your calendar subscription. This allows you to specify which event types should be displayed in your subscribed calendar to optimize your planning.'
                            )
                        }}
                    </p>
                </div>
            </SwitchGroup>

            <div v-if="aboForm.specific_event_types" class="mt-4 ml-0 md:ml-7">
                <Listbox v-model="selectedEventTypes" multiple as="div">
                    <ListboxLabel class="block text-sm font-medium leading-6 text-gray-900">
                        {{ $t('Select event types') }}
                    </ListboxLabel>

                    <div class="relative mt-2">
                        <ListboxButton
                            class="relative w-full cursor-default rounded-md bg-white min-h-10 py-2 pl-3 pr-10 text-left text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 focus:outline-none focus:ring-2 focus:ring-artwork-buttons-create sm:text-sm"
                        >
              <span class="block truncate">
                {{ selectedEventTypesLabel || $t('Choose…') }}
              </span>
                            <span class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-2">
                <component is="IconChevronDown" class="h-5 w-5 text-gray-400" aria-hidden="true" />
              </span>
                        </ListboxButton>

                        <transition
                            leave-active-class="transition ease-in duration-100"
                            leave-from-class="opacity-100"
                            leave-to-class="opacity-0"
                        >
                            <ListboxOptions
                                class="absolute z-10 mt-1 max-h-60 w-full overflow-auto rounded-md bg-white py-1 text-base shadow-lg ring-1 ring-black ring-opacity-5 focus:outline-none sm:text-sm"
                            >
                                <ListboxOption
                                    v-for="eventTyp in props.eventTypes"
                                    :key="eventTyp.id"
                                    :value="eventTyp"
                                    v-slot="{ active, selected }"
                                    as="template"
                                >
                                    <li
                                        :class="[
                      active ? 'bg-artwork-buttons-create text-white' : 'text-gray-900',
                      'relative cursor-default select-none py-2 pl-3 pr-9'
                    ]"
                                    >
                    <span :class="[selected ? 'font-semibold' : 'font-normal', 'block truncate']">
                      {{ eventTyp.name }}
                    </span>
                                        <span
                                            v-if="selected"
                                            :class="[
                        active ? 'text-white' : 'text-artwork-buttons-create',
                        'absolute inset-y-0 right-0 flex items-center pr-4'
                      ]"
                                        >
                      <component is="IconCircleCheck" class="h-5 w-5" aria-hidden="true" />
                    </span>
                                    </li>
                                </ListboxOption>
                            </ListboxOptions>
                        </transition>
                    </div>
                </Listbox>
            </div>
        </div>

        <!-- Abschnitt: Benachrichtigungen -->
        <div class="my-5">
            <SwitchGroup as="div" class="flex items-start gap-3">
                <Switch
                    v-model="aboForm.enable_notification"
                    :class="[
            aboForm.enable_notification ? 'bg-artwork-buttons-create' : 'bg-gray-200',
            'relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-artwork-buttons-create focus:ring-offset-2'
          ]"
                >
          <span
              aria-hidden="true"
              :class="[
              aboForm.enable_notification ? 'translate-x-5' : 'translate-x-0',
              'pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out'
            ]"
          />
                </Switch>
                <div class="text-sm leading-6">
                    <SwitchLabel class="font-medium text-gray-900">
                        {{ $t('Activate calendar notifications') }}
                    </SwitchLabel>
                    <p class="text-gray-500">
                        {{
                            $t(
                                "Receive timely notifications about upcoming events. Activate this option to receive reminders and alerts for your scheduled events and make sure you don't miss any important events."
                            )
                        }}
                    </p>
                </div>
            </SwitchGroup>

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
                            is-small
                        />
                    </div>

                    <div class="sm:col-span-2">
                        <!-- Native Select (bewusst kein BaseInput, klar getrennte Kontrolle) -->
                        <div class="relative">
                            <select
                                v-model="aboForm.notification_time_unit"
                                id="notification_time_unit"
                                name="notification_time_unit"
                                class="block w-full rounded-md border border-gray-200 bg-white py-2 px-3 text-sm focus:outline-none focus:ring-1 focus:ring-artwork-buttons-create"
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
                    <component is="IconInfoCircle" class="h-4 w-4" stroke-width="1.5" />
                    <span class="ml-1">{{ $t('Show instructions') }}</span>
                </button>
            </div>

            <FormButton
                @click="create"
                :text="aboForm.id ? $t('Save') : $t('Subscribe')"
                :disabled="aboForm.processing"
            />
        </div>

        <!-- Info-Hinweis -->
        <div
            v-if="aboForm.id"
            class="mt-3 text-artwork-buttons-create bg-artwork-buttons-create/10 rounded-lg p-3"
        >
            <div class="flex items-center gap-1 mb-2">
                <component is="IconInfoCircle" class="h-4 w-4" stroke-width="1.5" />
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
        />
    </BaseModal>
</template>

<style scoped>
/* keine zusätzlichen Styles nötig – alles via Utility-Klassen */
</style>
