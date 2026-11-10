<template>
    <ArtworkBaseModal
        title="Bulk Multi Edit"
        description="Edit multiple events at once"
        modal-size="sm:max-w-2xl"
        @close="emits('close')"
    >
        <BaseAlertComponent
            v-if="!hasEvents"
            type="error"
            message="No events selected"
            use-translation
        />

        <div
            v-if="validationErrors.length > 0"
            class="mb-4 rounded-md border border-danger-border bg-danger-surface px-3 py-2"
            role="alert"
            aria-live="assertive"
        >
            <p v-for="error in validationErrors" :key="error" class="text-sm text-danger">
                {{ $t(error) }}
            </p>
        </div>

        <form v-if="hasEvents" id="bulkMultiEditForm" @submit.prevent="submit">
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <ArtworkBaseListbox
                    v-if="usePage().props.event_status_module"
                    v-model="multiEditForm.selectedEventStatus"
                    :items="eventStatusOptions"
                    by="id"
                    option-label="name"
                    label="Event Status"
                    placeholder="No change"
                    show-color-indicator
                />
                <ArtworkBaseListbox
                    v-model="multiEditForm.selectedEventType"
                    :items="eventTypeOptions"
                    by="id"
                    option-label="name"
                    label="Event type"
                    placeholder="No change"
                    show-color-indicator
                    color-property="hex_code"
                />
                <ArtworkBaseListbox
                    v-model="multiEditForm.selectedRoom"
                    :items="roomOptions"
                    by="id"
                    option-label="name"
                    label="Room"
                    placeholder="No change"
                />
                <BaseInput
                    id="bulk-multi-edit-event-name"
                    v-model="multiEditForm.eventName"
                    class="col-span-full"
                    label="Event name"
                    :required="isEventNameRequired"
                    :placeholder="$t('No change')"
                />
                <BaseInput
                    id="bulk-multi-edit-day"
                    v-model="multiEditForm.selectedDay"
                    class="col-span-full"
                    type="date"
                    label="Day"
                />
                <BaseInput
                    id="bulk-multi-edit-start-time"
                    v-model="multiEditForm.selectedStartTime"
                    type="time"
                    label="Start time"
                />
                <BaseInput
                    id="bulk-multi-edit-end-time"
                    v-model="multiEditForm.selectedEndTime"
                    type="time"
                    label="End time"
                />
            </div>
        </form>
        <template #footer>
            <BaseUIButton
                label="Cancel"
                is-cancel-button
                hide-icon
                :disabled="processing"
                @click="emits('close')"
            />
            <BaseUIButton
                v-if="hasEvents"
                type="submit"
                form="bulkMultiEditForm"
                label="Save"
                variant="primary"
                icon="IconDeviceFloppy"
                :processing="processing"
            />
        </template>
    </ArtworkBaseModal>
</template>

<script setup>
import {computed, ref} from "vue";
import {useForm, usePage} from "@inertiajs/vue3";
import axios from "axios";
import {useI18n} from "vue-i18n";
import ArtworkBaseModal from "@/Artwork/Modals/ArtworkBaseModal.vue";
import ArtworkBaseListbox from "@/Artwork/Listbox/ArtworkBaseListbox.vue";
import BaseInput from "@/Artwork/Inputs/BaseInput.vue";
import BaseUIButton from "@/Artwork/Buttons/BaseUIButton.vue";
import BaseAlertComponent from "@/Components/Alerts/BaseAlertComponent.vue";
import {ticketingMoveHeaders} from "@/Composeables/useTicketingMove.js";

defineOptions({name: 'BulkMultiEditModal'})

const props = defineProps({
    eventIds: {
        type: Array,
        required: true
    },
    eventTypes: {
        type: Array,
        required: true
    },
    rooms: {
        type: Object,
        required: true
    },
    eventStatuses: {
        type: Object,
        required: false
    }
})

const emits = defineEmits(['close']);

const {t} = useI18n();
const validationErrors = ref([]);
const processing = ref(false);

const multiEditForm = useForm({
    eventIds: props.eventIds,
    selectedEventType: null,
    selectedRoom: null,
    selectedEventStatus: null,
    eventName: '',
    selectedDay: '',
    selectedStartTime: '',
    selectedEndTime: '',
})

const hasEvents = computed(() => multiEditForm.eventIds.length > 0)
const eventStatusOptions = computed(() => Object.values(props.eventStatuses ?? {}))
const eventTypeOptions = computed(() => Object.values(props.eventTypes ?? {}))
const roomOptions = computed(() => Object.values(props.rooms ?? {}))
const isEventNameRequired = computed(() => Boolean(multiEditForm.selectedEventType?.individual_name))

/**
 * Startzeit plus 30 Minuten als HH:MM (für <input type="time"> zweistellig).
 */
const addThirtyMinutes = (startTime) => {
    const [hours, minutes] = startTime.split(':').map(Number);
    const endTime = new Date();
    endTime.setHours(hours, minutes + 30, 0, 0);
    return `${String(endTime.getHours()).padStart(2, '0')}:${String(endTime.getMinutes()).padStart(2, '0')}`;
}

const submit = async () => {
    if (processing.value || !hasEvents.value) {
        return;
    }
    validationErrors.value = [];

    if (isEventNameRequired.value && !multiEditForm.eventName) {
        validationErrors.value.push('Event name is required for this event type');
        return;
    }

    // Startzeit ohne Endzeit: Ende = Start + 30 Minuten
    if (multiEditForm.selectedStartTime && !multiEditForm.selectedEndTime) {
        multiEditForm.selectedEndTime = addThirtyMinutes(multiEditForm.selectedStartTime);
    }

    processing.value = true;
    try {
        const moves = multiEditForm.selectedRoom || multiEditForm.selectedDay || multiEditForm.selectedStartTime || multiEditForm.selectedEndTime;
        const headers = moves ? await ticketingMoveHeaders(multiEditForm.eventIds) : {};
        if (!headers) {
            return;
        }

        await axios.post(route('events.bulk-multi-edit'), multiEditForm.data(), { headers, skipErrorToast: true }); // Fehler steht im Modal
        emits('close');
    } catch (error) {
        if (error?.response?.data?.errors) {
            validationErrors.value = Object.values(error.response.data.errors).flat();
        } else {
            validationErrors.value.push(t('An unexpected error occurred. Please try again.'));
        }
    } finally {
        processing.value = false;
    }
}
</script>
