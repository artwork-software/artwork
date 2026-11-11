<script>
import {useForm} from "@inertiajs/vue3";
import ArtworkBaseModal from "@/Artwork/Modals/ArtworkBaseModal.vue";
import ArtworkBaseListbox from "@/Artwork/Listbox/ArtworkBaseListbox.vue";
import BaseUIButton from "@/Artwork/Buttons/BaseUIButton.vue";
import BaseInput from "@/Artwork/Inputs/BaseInput.vue";
import BaseCheckbox from "@/Artwork/Inputs/BaseCheckbox.vue";
import BaseAlertComponent from "@/Components/Alerts/BaseAlertComponent.vue";

export default {
    name: "GeneralCalendarAboSettingModal",
    props: ['eventTypes', 'rooms', 'areas'],
    components: {
        ArtworkBaseModal,
        ArtworkBaseListbox,
        BaseUIButton,
        BaseInput,
        BaseCheckbox,
        BaseAlertComponent,
    },
    data(){
        return {
            aboForm: useForm({
                id: this.$page.props.auth.user.calendar_abo ? this.$page.props.auth.user.calendar_abo.id : null,
                date_range: this.$page.props.auth.user.calendar_abo ? this.$page.props.auth.user.calendar_abo.date_range : false,
                start_date: this.$page.props.auth.user.calendar_abo ? this.$page.props.auth.user.calendar_abo.start_date : '',
                end_date: this.$page.props.auth.user.calendar_abo ? this.$page.props.auth.user.calendar_abo.end_date : '',
                specific_event_types: this.$page.props.auth.user.calendar_abo ? this.$page.props.auth.user.calendar_abo.specific_event_types : false,
                event_types: [],
                specific_rooms: this.$page.props.auth.user.calendar_abo ? this.$page.props.auth.user.calendar_abo.specific_rooms : false,
                selected_rooms: [],
                specific_areas: this.$page.props.auth.user.calendar_abo ? this.$page.props.auth.user.calendar_abo.specific_areas : false,
                selected_areas: [],
                enable_notification: this.$page.props.auth.user.calendar_abo ? this.$page.props.auth.user.calendar_abo.enable_notification : false,
                notification_time:  this.$page.props.auth.user.calendar_abo ? this.$page.props.auth.user.calendar_abo.notification_time : 15,
                notification_time_unit: this.$page.props.auth.user.calendar_abo ? this.$page.props.auth.user.calendar_abo.notification_time_unit : 'minutes',
            }),
            event_types: this.$page.props.auth.user.calendar_abo ? this.eventTypes.filter((eventType) => this.$page.props.auth.user.calendar_abo.event_types.includes(eventType.id)) : [],
            selected_rooms: this.$page.props.auth.user.calendar_abo ? this.rooms.filter((room) => this.$page.props.auth.user.calendar_abo.selected_rooms.includes(room.id)) : [],
            selected_areas: this.$page.props.auth.user.calendar_abo ? this.areas.filter((area) => this.$page.props.auth.user.calendar_abo.selected_areas.includes(area.id)) : [],
            notificationTimeUnits: [
                {id: 'minutes', name: 'Minute(s)'},
                {id: 'hours', name: 'Hour(s)'},
                {id: 'days', name: 'Day(s)'},
            ],
        }
    },
    computed: {
        // Die Listbox arbeitet mit Objekten, das Formular speichert nur den Schlüssel
        selectedNotificationTimeUnit: {
            get() {
                return this.notificationTimeUnits.find((unit) => unit.id === this.aboForm.notification_time_unit) ?? null
            },
            set(unit) {
                this.aboForm.notification_time_unit = unit?.id ?? 'minutes'
            },
        },
    },
    methods: {
        closeModal(bool){
            this.$emit('close', bool)
        },
        create(){
            this.aboForm.event_types = this.event_types.map((eventType) => eventType.id)
            this.aboForm.selected_rooms = this.selected_rooms.map((room) => room.id)
            this.aboForm.selected_areas = this.selected_areas.map((area) => area.id)

            if ( this.aboForm.id ) {
                this.aboForm.patch(route('user.calendar.abo.update', this.aboForm.id), {
                    preserveScroll: true,
                    preserveState: true,
                    onSuccess: () => {
                        this.closeModal(true)
                    }
                })
            } else {
                this.aboForm.post(route('user.calendar.abo.create'), {
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
    <ArtworkBaseModal
        @close="closeModal(false)"
        title="Calendar subscription settings"
        description="Customize your calendar subscription to suit your individual needs. Select a specific time period, certain appointment types and activate notifications to stay optimally informed and make your planning easier. Use these settings to configure your calendar according to your preferences and stay organized."
    >
        <div class="divide-y divide-border-subtle">
            <div class="pb-5">
                <BaseCheckbox
                    id="date_range"
                    v-model="aboForm.date_range"
                    :label="$t('Set period for calendar subscription')"
                    :description="$t('Select a specific period for your calendar subscription. If you do not select a period, the subscription will continue indefinitely. This allows you to subscribe to the calendar for a set period of time only.')"
                />
                <div v-if="aboForm.date_range" class="mt-3 ml-7 grid grid-cols-1 gap-3 md:grid-cols-2">
                    <BaseInput
                        id="start_date"
                        type="date"
                        v-model="aboForm.start_date"
                        label="Period Start"
                        :error="aboForm.errors.start_date"
                    />
                    <BaseInput
                        id="end_date"
                        type="date"
                        v-model="aboForm.end_date"
                        label="Period End"
                        :error="aboForm.errors.end_date"
                    />
                </div>
            </div>

            <div class="py-5">
                <BaseCheckbox
                    id="specific_event_types"
                    v-model="aboForm.specific_event_types"
                    :label="$t('Select event types for calendar subscription')"
                    :description="$t('Select specific types of events for your calendar subscription. This allows you to specify which event types should be displayed in your subscribed calendar to optimize your planning.')"
                />
                <div v-if="aboForm.specific_event_types" class="mt-3 ml-7">
                    <ArtworkBaseListbox
                        v-model="event_types"
                        :items="eventTypes"
                        by="id"
                        option-label="name"
                        label="Select event types"
                        multiple
                        show-color-indicator
                        color-property="hex_code"
                    />
                </div>
            </div>

            <div class="py-5">
                <BaseCheckbox
                    id="specific_areas"
                    v-model="aboForm.specific_areas"
                    :label="$t('Select areas for calendar subscription')"
                    :description="$t('Select specific areas for your calendar subscription. This allows you to define which areas should be displayed in your subscribed calendar to optimize your planning.')"
                />
                <div v-if="aboForm.specific_areas" class="mt-3 ml-7">
                    <ArtworkBaseListbox
                        v-model="selected_areas"
                        :items="areas"
                        by="id"
                        option-label="name"
                        label="Select areas"
                        multiple
                    />
                </div>
            </div>

            <div class="py-5">
                <BaseCheckbox
                    id="specific_rooms"
                    v-model="aboForm.specific_rooms"
                    :label="$t('Select rooms for calendar subscription')"
                    :description="$t('Select specific rooms for your calendar subscription. This allows you to define which rooms should be displayed in your subscribed calendar to optimize your planning.')"
                />
                <div v-if="aboForm.specific_rooms" class="mt-3 ml-7">
                    <ArtworkBaseListbox
                        v-model="selected_rooms"
                        :items="rooms"
                        by="id"
                        option-label="name"
                        label="Select rooms"
                        multiple
                    />
                </div>
            </div>

            <div class="pt-5">
                <BaseCheckbox
                    id="enable_notification"
                    v-model="aboForm.enable_notification"
                    :label="$t('Activate calendar notifications')"
                    :description="$t('Receive timely notifications about upcoming events. Activate this option to receive reminders and alerts for your scheduled events and make sure you don\'t miss any important events.')"
                />
                <div v-if="aboForm.enable_notification" class="mt-3 ml-7 grid grid-cols-2 gap-3">
                    <BaseInput
                        id="notification_time"
                        type="number"
                        v-model="aboForm.notification_time"
                        label="Notification settings"
                        min="0"
                        :error="aboForm.errors.notification_time"
                    />
                    <ArtworkBaseListbox
                        v-model="selectedNotificationTimeUnit"
                        :items="notificationTimeUnits"
                        by="id"
                        option-label="name"
                        label="Unit"
                        use-translations
                    />
                </div>
            </div>
        </div>

        <BaseAlertComponent
            v-if="aboForm.id"
            class="mt-5 mb-0!"
            type="info"
            title="Information"
            message="As soon as you click on “Save”, your subscription will be updated and the settings will be saved. If you have subscribed to the calendar via the link, your entries in the calendar program will be updated automatically. Alternatively, you can also download the ICS file and then insert it into your calendar program."
            use-translation
        />

        <template #footer>
            <BaseUIButton
                v-if="aboForm.id"
                class="mr-auto"
                label="Show instructions"
                variant="ghost"
                icon="IconInfoCircle"
                @click="closeModal(true)"
            />
            <BaseUIButton
                label="Cancel"
                is-cancel-button
                hide-icon
                :disabled="aboForm.processing"
                @click="closeModal(false)"
            />
            <BaseUIButton
                :label="aboForm.id ? 'Save' : 'Subscribe'"
                variant="primary"
                :icon="aboForm.id ? 'IconDeviceFloppy' : 'IconCalendarPlus'"
                :processing="aboForm.processing"
                :disabled="aboForm.processing"
                @click="create"
            />
        </template>
    </ArtworkBaseModal>
</template>
