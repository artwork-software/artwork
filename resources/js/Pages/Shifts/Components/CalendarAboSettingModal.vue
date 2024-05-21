<script>
import BaseModal from "@/Components/Modals/BaseModal.vue";
import {Switch, SwitchGroup, SwitchLabel, Listbox, ListboxButton, ListboxLabel, ListboxOption, ListboxOptions} from "@headlessui/vue";
import {useForm} from "@inertiajs/inertia-vue3";
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
    <BaseModal v-if="true" @closed="closeModal(true)">
        <div class="my-3">
            <SwitchGroup as="div" class="flex items-center gap-3">
                <SwitchLabel as="span" class="mr-3 text-sm">
                    <span class="font-medium text-gray-900" :class="aboForm.date_range ? '!text-gray-400' : ''">Kein bestimmten Zeitraum Abonnieren</span>
                </SwitchLabel>
                <Switch v-model="aboForm.date_range" :class="[aboForm.date_range ? 'bg-artwork-buttons-create' : 'bg-gray-200', 'relative inline-flex h-3 w-8 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-indigo-600 focus:ring-offset-2']">
                    <span aria-hidden="true" :class="[aboForm.date_range ? 'translate-x-5' : 'translate-x-0', 'pointer-events-none inline-block h-2 w-2 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out']" />
                </Switch>
                <SwitchLabel as="span" class="ml-3 text-sm">
                    <span class="font-medium text-gray-900" :class="!aboForm.date_range ? '!text-gray-400' : ''">Ein bestimmten Zeitraum Abonnieren</span>
                </SwitchLabel>
            </SwitchGroup>
        </div>
        <div v-if="aboForm.date_range">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                <div>
                    <label for="start_date" class="block text-sm font-medium leading-6 text-gray-900">Start Datum</label>
                    <div class="mt-2">
                        <input type="date" v-model="aboForm.start_date" id="start_date" class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6" placeholder="you@example.com" />
                    </div>
                </div>
                <div>
                    <label for="end_date" class="block text-sm font-medium leading-6 text-gray-900">End Datum</label>
                    <div class="mt-2">
                        <input type="date" v-model="aboForm.end_date" id="end_date" class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6" placeholder="you@example.com" />
                    </div>
                </div>
            </div>
        </div>
        <div class="my-3">
            <SwitchGroup as="div" class="flex items-center gap-3">
                <SwitchLabel as="span" class="mr-3 text-sm">
                    <span class="font-medium text-gray-900" :class="aboForm.specific_event_types ? '!text-gray-400' : ''">Keine bestimmten Terminarten Abonnieren</span>
                </SwitchLabel>
                <Switch v-model="aboForm.specific_event_types" :class="[aboForm.specific_event_types ? 'bg-artwork-buttons-create' : 'bg-gray-200', 'relative inline-flex h-3 w-8 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-indigo-600 focus:ring-offset-2']">
                    <span aria-hidden="true" :class="[aboForm.specific_event_types ? 'translate-x-5' : 'translate-x-0', 'pointer-events-none inline-block h-2 w-2 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out']" />
                </Switch>
                <SwitchLabel as="span" class="ml-3 text-sm">
                    <span class="font-medium text-gray-900" :class="!aboForm.specific_event_types ? '!text-gray-400' : ''">Bestimmte Terminarten Abonnieren</span>
                </SwitchLabel>
            </SwitchGroup>
        </div>
        <div v-if="aboForm.specific_event_types">
            <Listbox as="div" v-model="event_types" multiple>
                <ListboxLabel class="block text-sm font-medium leading-6 text-gray-900">
                    Terminarten
                </ListboxLabel>
                <div class="relative mt-2">
                    <ListboxButton class="relative w-full cursor-default rounded-md bg-white min-h-10 py-1.5 pl-3 pr-10 text-left text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 focus:outline-none focus:ring-2 focus:ring-indigo-600 sm:text-sm sm:leading-6">
                        <span class="block truncate">{{ event_types?.map((eventType) => eventType?.name).join(', ') }}</span>
                        <span class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-2">
                          <IconChevronDown class="h-5 w-5 text-gray-400" aria-hidden="true" />
                        </span>
                    </ListboxButton>

                    <transition leave-active-class="transition ease-in duration-100" leave-from-class="opacity-100" leave-to-class="opacity-0">
                        <ListboxOptions class="absolute z-10 mt-1 max-h-60 w-full overflow-auto rounded-md bg-white py-1 text-base shadow-lg ring-1 ring-black ring-opacity-5 focus:outline-none sm:text-sm">
                            <ListboxOption as="template" v-for="eventTyp in eventTypes" :key="eventTyp.id" :value="eventTyp" v-slot="{ active, selected }">
                                <li :class="[active ? 'bg-indigo-600 text-white' : 'text-gray-900', 'relative cursor-default select-none py-2 pl-3 pr-9']">
                                    <span :class="[selected ? 'font-semibold' : 'font-normal', 'block truncate']">{{ eventTyp.name }}</span>

                                    <span v-if="selected" :class="[active ? 'text-white' : 'text-indigo-600', 'absolute inset-y-0 right-0 flex items-center pr-4']">
                                        <IconCircleCheck class="h-5 w-5" aria-hidden="true" />
                                    </span>
                                </li>
                            </ListboxOption>
                        </ListboxOptions>
                    </transition>
                </div>
            </Listbox>
        </div>
        <div class="my-3">
            <div class="relative flex items-start">
                <div class="flex h-6 items-center">
                    <input v-model="aboForm.enable_notification" id="comments" aria-describedby="comments-description" name="comments" type="checkbox" class="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-600" />
                </div>
                <div class="ml-3 text-sm leading-6">
                    <label for="comments" class="font-medium text-gray-900">Benachrichtigungen vom Kalender</label>
                    <p id="comments-description" class="text-gray-500">
                        Erhalten Sie Benachrichtigungen vor anstehenden Terminen.
                    </p>
                </div>
            </div>
        </div>
        <div v-if="aboForm.enable_notification">
            <div>
                <label for="phone-number" class="block text-sm font-medium leading-6 text-gray-900">
                    Benachrichtigung-Einstellungen
                </label>
                <div class="relative mt-2 rounded-md shadow-sm">
                    <input type="text" v-model="aboForm.notification_time" name="phone-number" id="phone-number" class="block w-full rounded-md border-0 py-1.5 pl-2 text-gray-900 ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6"/>
                    <div class="absolute inset-y-0 right-0 flex items-center">
                        <label for="country" class="sr-only">Country</label>
                        <select v-model="aboForm.notification_time_unit" id="country" name="country" autocomplete="country" class="h-full rounded-md border-0 bg-transparent py-0 pl-3 pr-7 text-gray-500 focus:outline-none focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm">
                            <option value="minutes">Minuten</option>
                            <option value="hours">Stunden</option>
                            <option value="days">Tage</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>

        <FormButton @click="create" class="mt-4" text="Abonnieren"></FormButton>

    </BaseModal>
</template>

<style scoped>

</style>
