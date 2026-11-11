<template>
    <ArtworkBaseModal
        :title="subEventToEdit ? 'Edit sub-event' : 'New sub-event'"
        description="Please note that the appointment must take place within the appointment group period."
        modal-size="sm:max-w-2xl"
        z-index="150"
        @close="closeModal(false)"
    >
        <form id="addSubEventForm" class="space-y-4" @submit.prevent="updateOrCreateEvent">
            <p class="text-xs text-text-muted">
                {{ $t('Belongs to Blocker in {0}', [event?.roomName ?? event?.room?.name ?? '']) }}
            </p>

            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <ArtworkBaseListbox
                    v-model="subEvent.selectedEventType"
                    :items="filteredEventTypes"
                    by="id"
                    option-label="name"
                    show-color-indicator
                    color-property="hex_code"
                    label="Event type"
                />
                <BaseInput
                    id="subEventTitle"
                    v-model="subEvent.eventName"
                    label="Event name"
                    :required="Boolean(subEvent.selectedEventType?.individual_name)"
                />
            </div>

            <div v-if="(event_properties?.length || 0) > 0">
                <p class="mb-1 font-lexend text-xs font-medium text-text-muted">{{ $t('Properties') }}</p>
                <div class="flex flex-wrap gap-2">
                    <label
                        v-for="eventProperty in event_properties"
                        :key="eventProperty.id"
                        class="inline-flex h-[26px] cursor-pointer items-center gap-1.5 rounded-full border px-3 text-xs transition-colors duration-150 has-[:focus-visible]:outline-2 has-[:focus-visible]:outline-offset-2 has-[:focus-visible]:outline-accent-600 motion-reduce:transition-none"
                        :class="eventProperty.checked ? 'border-accent-200 bg-accent-50 text-accent-700' : 'border-border bg-surface text-text-muted hover:bg-surface-hover'"
                    >
                        <input v-model="eventProperty.checked" type="checkbox" class="sr-only" />
                        <PropertyIcon v-if="eventProperty.checked" name="IconCheck" class="size-3.5" aria-hidden="true" />
                        <PropertyIcon :name="eventProperty.icon" class="size-3.5" aria-hidden="true" />
                        <span>{{ eventProperty.name }}</span>
                    </label>
                </div>
            </div>

            <ArtworkBaseToggle
                id="subEventAllDay"
                v-model="allDayEvent"
                label="Full day"
                use-translation
                is-small
            />

            <div class="grid grid-cols-2 gap-4">
                <BaseInput
                    id="subEventStartDate"
                    v-model="startDate"
                    type="date"
                    label="Start date"
                    disabled
                    :class="allDayEvent ? 'col-span-2 md:col-span-1' : ''"
                />
                <BaseInput
                    v-if="!allDayEvent"
                    id="subEventStartTime"
                    v-model="startTime"
                    type="time"
                    label="Start time"
                    required
                    @change="checkTimes()"
                />
                <BaseInput
                    id="subEventEndDate"
                    v-model="endDate"
                    type="date"
                    label="End date"
                    disabled
                    :class="allDayEvent ? 'col-span-2 md:col-span-1' : ''"
                />
                <BaseInput
                    v-if="!allDayEvent"
                    id="subEventEndTime"
                    v-model="endTime"
                    type="time"
                    label="End time"
                    required
                    @change="checkTimes()"
                />
            </div>
            <div v-if="helpText || helpTextLength || requestError" class="space-y-1">
                <p v-if="helpText" class="text-[11.5px] text-danger">{{ helpText }}</p>
                <p v-if="helpTextLength" class="text-[11.5px] text-danger">{{ helpTextLength }}</p>
                <p v-if="requestError" class="text-[11.5px] text-danger">{{ requestError }}</p>
            </div>

            <BaseTextarea
                id="subEventDescription"
                v-model="subEvent.description"
                label="What do I need to bear in mind for the event?"
                rows="4"
            />
        </form>
        <template #footer>
            <BaseUIButton
                label="Cancel"
                is-cancel-button
                hide-icon
                :disabled="processing"
                @click="closeModal(false)"
            />
            <BaseUIButton
                type="submit"
                form="addSubEventForm"
                label="Vouchers"
                variant="primary"
                icon="IconCalendarPlus"
                :processing="processing"
                :disabled="!canSubmit"
            />
        </template>
    </ArtworkBaseModal>
</template>

<script>
import {inject} from "vue";
import axios from "axios";
import dayjs from "dayjs";
import {useForm} from "@inertiajs/vue3";
import Permissions from "@/Mixins/Permissions.vue";
import {useEvent} from "@/Composeables/Event.js";
import ArtworkBaseModal from "@/Artwork/Modals/ArtworkBaseModal.vue";
import ArtworkBaseListbox from "@/Artwork/Listbox/ArtworkBaseListbox.vue";
import ArtworkBaseToggle from "@/Artwork/Toggles/ArtworkBaseToggle.vue";
import BaseInput from "@/Artwork/Inputs/BaseInput.vue";
import BaseTextarea from "@/Artwork/Inputs/BaseTextarea.vue";
import BaseUIButton from "@/Artwork/Buttons/BaseUIButton.vue";
import PropertyIcon from "@/Artwork/Icon/PropertyIcon.vue";

const {getDaysOfEvent, formatEventDateByDayJs} = useEvent();
export default {
    name: "AddSubEventModal",
    mixins: [Permissions],
    components: {
        ArtworkBaseModal,
        ArtworkBaseListbox,
        ArtworkBaseToggle,
        BaseInput,
        BaseTextarea,
        BaseUIButton,
        PropertyIcon,
    },
    setup() {
        const event_properties = inject('event_properties', []);
        return {event_properties}
    },
    props: ['event', 'eventTypes', 'subEventToEdit'],
    emits: ['close'],
    data() {
        return {
            subEvent: useForm({
                event_id: this.event?.id,
                eventName: this.subEventToEdit?.eventName ? this.subEventToEdit?.eventName : '',
                selectedEventType: this.subEventToEdit?.type ? this.subEventToEdit?.type : (this.eventTypes?.[1] ?? null),
                start_time: this.subEventToEdit?.start ? this.subEventToEdit?.start : dayjs(this.event?.start).format('YYYY-MM-DD HH:mm'),
                end_time: this.subEventToEdit?.end ? this.subEventToEdit?.end : dayjs(this.event?.end).format('YYYY-MM-DD HH:mm'),
                description: this.subEventToEdit?.description ? this.subEventToEdit?.description : '',
                user_id: this.$page.props.auth.user.id,
                event_type_id: this.subEventToEdit?.eventTypeId ? this.subEventToEdit?.eventTypeId : '',
                allDay: this.subEventToEdit?.allDay ? this.subEventToEdit?.allDay : false,
                eventProperties: this.subEventToEdit?.event_properties ? this.subEventToEdit?.event_properties : [],
            }),
            edit: !!this.subEventToEdit?.id,
            helpText: '',
            helpTextLength: '',
            requestError: '',
            processing: false,
            allDayEvent: this.subEventToEdit?.allDay ? this.subEventToEdit.allDay : false,
            startTime: this.subEventToEdit?.start ? dayjs(this.subEventToEdit?.start).format('HH:mm') : dayjs(this.event?.start).format('HH:mm'),
            endTime: this.subEventToEdit?.end ? dayjs(this.subEventToEdit?.end).format('HH:mm') : dayjs(this.event?.end).format('HH:mm'),
            startDate: this.subEventToEdit?.start ? dayjs(this.subEventToEdit?.start).format('YYYY-MM-DD') : dayjs(this.event?.start).format('YYYY-MM-DD'),
            endDate: this.subEventToEdit?.end ? dayjs(this.subEventToEdit?.end).format('YYYY-MM-DD') : dayjs(this.event?.end).format('YYYY-MM-DD'),
        }
    },
    computed: {
        filteredEventTypes() {
            return (this.eventTypes ?? []).filter(eventType => eventType.id !== 1)
        },
        canSubmit() {
            if (this.processing || this.helpText || this.helpTextLength) {
                return false;
            }
            if (this.subEvent.selectedEventType?.individual_name) {
                return Boolean(this.subEvent.eventName?.trim());
            }
            return true;
        },
    },
    created() {
        // Eigenschaften einmalig aus dem bearbeiteten Untertermin übernehmen. Vorher lief das
        // in einem tiefen Watcher auf das Formular und setzte die Auswahl bei jeder Eingabe zurück.
        (this.event_properties ?? []).forEach((eventProperty) => {
            eventProperty.checked = (this.subEventToEdit?.event_properties ?? []).some(
                (subEventProperty) => subEventProperty.id === eventProperty.id
            );
        });
    },
    watch: {
        allDayEvent() {
            this.checkTimes()
        },
    },
    methods: {
        closeModal(bool, desiredRoomIds, desiredDays) {
            if (this.processing && !bool) {
                return;
            }
            this.$emit('close', bool, desiredRoomIds, desiredDays);
        },
        formatDate(date, time) {
            if (date === null || time === null) return null;
            return (new Date(date + ' ' + time)).toISOString()
        },
        checkTimes() {
            this.subEvent.allDay = this.allDayEvent;
            if (this.allDayEvent) {
                this.handleAllDayEventChange()
            }
            this.subEvent.start_time = dayjs(this.formatDate(this.startDate, this.startTime)).format('YYYY-MM-DD HH:mm');
            this.subEvent.end_time = dayjs(this.formatDate(this.endDate, this.endTime)).format('YYYY-MM-DD HH:mm');
            if (this.subEvent.start_time > this.subEvent.end_time && this.subEvent.end_time && this.subEvent.start_time) {
                this.helpText = this.$t('The end time cannot be before the start time!');
            } else {
                this.helpText = '';
            }

            // Termin muss mindestens 30 Minuten dauern
            if (this.subEvent.start_time && this.subEvent.end_time) {
                const date = new Date(this.subEvent.start_time);
                const minimumEnd = this.addMinutes(date, 30);
                if (minimumEnd <= new Date(this.subEvent.end_time)) {
                    this.helpTextLength = '';
                } else {
                    this.helpTextLength = this.$t('The event must not be shorter than 30 minutes');
                }
            }
        },
        addMinutes(date, minutes) {
            date.setMinutes(date.getMinutes() + minutes);
            return date;
        },
        handleAllDayEventChange() {
            if (this.allDayEvent) {
                // Ganztägig: 00:00 bis 23:59
                this.startTime = "00:00";
                this.endTime = "23:59";
            }
        },
        updateOrCreateEvent() {
            if (!this.canSubmit) {
                return;
            }
            const onSuccess = () => {
                this.closeModal(
                    true,
                    [this.event.roomId],
                    getDaysOfEvent(
                        // Hauptermin für das Nachladen nutzen, Untertermine liegen am selben Tag
                        formatEventDateByDayJs(this.event.start),
                        formatEventDateByDayJs(this.event.end)
                    )
                );
            };

            this.subEvent.event_type_id = this.subEvent?.selectedEventType?.id;

            const payload = {
                event_id: this.subEvent.event_id,
                eventName: this.subEvent.eventName,
                selectedEventType: this.subEvent.selectedEventType,
                start_time: this.subEvent.start_time,
                end_time: this.subEvent.end_time,
                description: this.subEvent.description,
                user_id: this.subEvent.user_id,
                event_type_id: this.subEvent.event_type_id,
                allDay: this.subEvent.allDay,
                eventProperties: (this.event_properties ?? [])
                    .filter((eventProperty) => eventProperty.checked)
                    .map((eventProperty) => eventProperty.id),
            };

            this.processing = true;
            this.requestError = '';
            const request = this.edit
                ? axios.patch(route('subEvent.update', this.subEventToEdit.id), payload)
                : axios.post(route('subEvent.add'), payload);

            // Erst nach Erfolg schließen; bei Fehlern bleibt das Modal mit Hinweis offen
            request
                .then(() => onSuccess())
                .catch((error) => {
                    this.requestError = error?.response?.data?.message
                        ?? this.$t('Something went wrong while saving. Please try again.');
                })
                .finally(() => {
                    this.processing = false;
                });
        }
    },
}
</script>
