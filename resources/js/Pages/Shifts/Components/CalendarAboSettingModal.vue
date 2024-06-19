<script>
import BaseModal from "@/Components/Modals/BaseModal.vue";
import {Switch, SwitchGroup, SwitchLabel, Listbox, ListboxButton, ListboxLabel, ListboxOption, ListboxOptions} from "@headlessui/vue";
import {useForm} from "@inertiajs/vue3";
import IconLib from "@/Mixins/IconLib.vue";
import FormButton from "@/Layouts/Components/General/Buttons/FormButton.vue";
import CalendarAboInfoModal from "@/Pages/Shifts/Components/CalendarAboInfoModal.vue";

export default {
    name: "CalendarAboSettingModal",
    mixins: [IconLib],
    components: {
        CalendarAboInfoModal,
        FormButton,
        BaseModal, SwitchGroup, Switch, SwitchLabel,
        Listbox, ListboxButton, ListboxLabel, ListboxOption, ListboxOptions
    },
    props: ['eventTypes'],
    emits: ['close'],
    data() {
        return {
            aboForm: useForm({
                id: this.$page.props.user.shift_calendar_abo ? this.$page.props.user.shift_calendar_abo.id : null,
                date_range: this.$page.props.user.shift_calendar_abo ? this.$page.props.user.shift_calendar_abo.date_range : false,
                start_date: this.$page.props.user.shift_calendar_abo ? this.$page.props.user.shift_calendar_abo.start_date : null,
                end_date: this.$page.props.user.shift_calendar_abo ? this.$page.props.user.shift_calendar_abo.end_date : null,
                specific_event_types: this.$page.props.user.shift_calendar_abo ? this.$page.props.user.shift_calendar_abo.specific_event_types : false,
                event_types: [],
                enable_notification: this.$page.props.user.shift_calendar_abo ? this.$page.props.user.shift_calendar_abo.enable_notification : false,
                notification_time: this.$page.props.user.shift_calendar_abo ? this.$page.props.user.shift_calendar_abo.notification_time : 0,
                notification_time_unit: this.$page.props.user.shift_calendar_abo ? this.$page.props.user.shift_calendar_abo.notification_time_unit : 'minutes',
            }),
            event_types: this.$page.props.user.shift_calendar_abo ? this.eventTypes.filter((eventType) => this.$page.props.user.shift_calendar_abo.event_types.includes(eventType.id)) : [],
            showCalendarAboInfoModal: false
        }
    },
    methods: {
        closeModal(bool){
            this.$emit('close', bool)
        },
        create(){
            this.aboForm.event_types = this.event_types.map((eventType) => eventType.id)
            if ( this.aboForm.id ) {
                this.aboForm.patch(route('user.shift.calendar.abo.update', this.aboForm.id), {
                    preserveScroll: true,
                    preserveState: true,
                    onSuccess: () => {
                        this.closeModal(true)


                    }
                })
            } else {
                this.aboForm.post(route('user.shift.calendar.abo.create'), {
                    preserveScroll: true,
                    preserveState: true,
                    onSuccess: () => {
                        this.closeModal(true)

                    }
                })
            }
        }
    }
}
</script>

<template>
    <BaseModal v-if="true" @closed="closeModal(false)" modal-image="/Svgs/Overlays/illu_appointment_new.svg">
        <div class="mb-5">
            <h2 class="headline1 mb-6">{{ $t('Shift Calendar subscription settings') }}</h2>
            <p class="text-secondary subpixel-antialiased text-sm">
                {{ $t('Customize your calendar subscription to suit your individual needs. Select a specific time period, certain appointment types and activate notifications to stay optimally informed and make your planning easier. Use these settings to configure your calendar according to your preferences and stay organized.') }}
            </p>
        </div>
        <div class="my-5 border-b border-dotted border-gray-200 pb-5">
            <div class="relative flex items-start">
                <div class="flex h-6 items-center">
                    <input v-model="aboForm.date_range" id="date_range" aria-describedby="date_range-description" name="date_range" type="checkbox" class="h-4 w-4 border-gray-300 text-green-600 focus:ring-gray-600" />
                </div>
                <div class="ml-3 text-sm leading-6">
                    <label for="date_range" class="font-medium text-gray-900">{{ $t('Set period for calendar subscription') }}</label>
                    <p id="date_range-description" class="text-gray-500">
                        {{ $t('Select a specific period for your calendar subscription. If you do not select a period, the subscription will continue indefinitely. This allows you to subscribe to the calendar for a set period of time only.') }}
                    </p>
                </div>
            </div>
            <div v-if="aboForm.date_range" class="mt-3 ml-7">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    <div>
                        <label for="start_date" class="block text-sm font-medium leading-6 text-gray-900">{{ $t('Period Start') }}</label>
                        <div class="mt-2">
                            <input type="date" v-model="aboForm.start_date" id="start_date" class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6" placeholder="you@example.com" />
                        </div>
                    </div>
                    <div>
                        <label for="end_date" class="block text-sm font-medium leading-6 text-gray-900">{{ $t('Period End') }}</label>
                        <div class="mt-2">
                            <input type="date" v-model="aboForm.end_date" id="end_date" class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6" placeholder="you@example.com" />
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="my-5 border-b border-dotted border-gray-200 pb-5">
            <div class="relative flex items-start">
                <div class="flex h-6 items-center">
                    <input v-model="aboForm.specific_event_types" id="specific_event_types" aria-describedby="specific_event_types-description" name="specific_event_types" type="checkbox" class="h-4 w-4 border-gray-300 text-green-600 focus:ring-gray-600" />
                </div>
                <div class="ml-3 text-sm leading-6">
                    <label for="specific_event_types" class="font-medium text-gray-900">
                        {{ $t('Select event types for calendar subscription') }}
                    </label>
                    <p id="specific_event_types-description" class="text-gray-500">
                        {{ $t('Select specific types of events for your calendar subscription. This allows you to specify which event types should be displayed in your subscribed calendar to optimize your planning.') }}
                    </p>
                </div>
            </div>
            <div v-if="aboForm.specific_event_types" class="mt-3 ml-7">
                <Listbox as="div" v-model="event_types" multiple>
                    <ListboxLabel class="block text-sm font-medium leading-6 text-gray-900">
                        {{ $t('Select event types') }}
                    </ListboxLabel>
                    <div class="relative mt-2">
                        <ListboxButton class="relative w-full cursor-default rounded-md bg-white min-h-10 py-1.5 pl-3 pr-10 text-left text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 focus:outline-none focus:ring-2 focus:ring-artwork-buttons-create sm:text-sm sm:leading-6">
                            <span class="block truncate">{{ event_types?.map((eventType) => eventType?.name).join(', ') }}</span>
                            <span class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-2">
                          <IconChevronDown class="h-5 w-5 text-gray-400" aria-hidden="true" />
                        </span>
                        </ListboxButton>

                        <transition leave-active-class="transition ease-in duration-100" leave-from-class="opacity-100" leave-to-class="opacity-0">
                            <ListboxOptions class="absolute z-10 mt-1 max-h-60 w-full overflow-auto rounded-md bg-white py-1 text-base shadow-lg ring-1 ring-black ring-opacity-5 focus:outline-none sm:text-sm">
                                <ListboxOption as="template" v-for="eventTyp in eventTypes" :key="eventTyp.id" :value="eventTyp" v-slot="{ active, selected }">
                                    <li :class="[active ? 'bg-artwork-buttons-create text-white' : 'text-gray-900', 'relative cursor-default select-none py-2 pl-3 pr-9']">
                                        <span :class="[selected ? 'font-semibold' : 'font-normal', 'block truncate']">{{ eventTyp.name }}</span>

                                        <span v-if="selected" :class="[active ? 'text-white' : 'text-artwork-buttons-create', 'absolute inset-y-0 right-0 flex items-center pr-4']">
                                        <IconCircleCheck class="h-5 w-5" aria-hidden="true" />
                                    </span>
                                    </li>
                                </ListboxOption>
                            </ListboxOptions>
                        </transition>
                    </div>
                </Listbox>
            </div>
        </div>

        <div class="my-5">
            <div class="relative flex items-start">
                <div class="flex h-6 items-center">
                    <input v-model="aboForm.enable_notification" id="enable_notification" aria-describedby="enable_notification-description" name="enable_notification" type="checkbox" class="h-4 w-4 border-gray-300 text-green-600 focus:ring-gray-600" />
                </div>
                <div class="ml-3 text-sm leading-6">
                    <label for="enable_notification" class="font-medium text-gray-900">{{ $t('Activate calendar notifications') }}</label>
                    <p id="enable_notification-description" class="text-gray-500">
                        {{ $t("Receive timely notifications about upcoming events. Activate this option to receive reminders and alerts for your scheduled events and make sure you don't miss any important events.") }}
                    </p>
                </div>
            </div>
            <div v-if="aboForm.enable_notification" class="mt-3 ml-7">
                <div>
                    <label for="phone-number" class="block text-sm font-medium leading-6 text-gray-900">
                        {{ $t('Notification settings') }}
                    </label>
                    <div class="relative mt-2 rounded-md shadow-sm">
                        <input type="text" v-model="aboForm.notification_time" name="notification_time" id="notification_time" class="block w-full rounded-md border-0 py-1.5 text-gray-900 ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6"/>
                        <div class="absolute inset-y-0 right-0 flex items-center">
                            <select v-model="aboForm.notification_time_unit" id="notification_time_unit" name="notification_time_unit" autocomplete="notification_time_unit" class="h-full rounded-md border-0 bg-transparent py-0 pl-3 pr-7 text-gray-500 focus:outline-none focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm">
                                <option value="minutes">{{ $t('Minute(s)') }}</option>
                                <option value="hours">{{ $t('Hour(s)') }}</option>
                                <option value="days">{{ $t('Day(s)') }}</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
        </div>


        <div class="flex items-center justify-between">
            <div>
                <div v-if="aboForm.id" class="flex items-center text-xs text-artwork-buttons-hover underline cursor-pointer" @click="closeModal(true)">
                    <IconInfoCircle class="h-4 w-4" stroke-width="1.5"/>
                    <span class="ml-1">{{ $t('Show instructions') }}</span>
                </div>
            </div>
            <FormButton @click="create(true)" :text="aboForm.id ? $t('Save') : $t('Subscribe')" />
        </div>

        <div v-if="aboForm.id" class="mt-2 text-artwork-buttons-create bg-artwork-buttons-create/10 rounded-lg p-3" >
            <div class="flex items-center gap-1 mb-2">
                <IconInfoCircle class="h-4 w-4" stroke-width="1.5"/>
                <h5 class="font-bold text-sm">{{ $t('Information') }}</h5>
            </div>
            <div class="text-xs text-artwork-buttons-create w-fit">
                {{ $t('As soon as you click on “Save”, your subscription will be updated and the settings will be saved. If you have subscribed to the calendar via the link, your entries in the calendar program will be updated automatically. Alternatively, you can also download the ICS file and then insert it into your calendar program.') }}
            </div>
        </div>


    </BaseModal>
</template>

<style scoped>

</style>
