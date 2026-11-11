<template>
    <ArtworkBaseModal
        title="Edit all series events"
        description="Would you like to move all events of this series to another room or by a certain period of time?"
        modal-size="sm:max-w-xl"
        z-index="150"
        @close="closeModal"
    >
        <form id="editSeriesEventsForm" class="space-y-4" @submit.prevent="saveSeriesEdit">
            <ArtworkBaseListbox
                v-model="selectedRoom"
                :items="roomOptions"
                by="id"
                option-label="name"
                label="Room"
            />
            <div class="grid grid-cols-1 items-end gap-2 sm:grid-cols-8">
                <ArtworkBaseListbox
                    v-model="selectedCalculationType"
                    :items="calculationTypes"
                    by="id"
                    option-label="type"
                    :enable-search="false"
                    class="sm:col-span-2"
                />
                <BaseInput
                    id="seriesEditValue"
                    v-model="editValue"
                    type="number"
                    min="0"
                    max="999"
                    class="sm:col-span-3"
                />
                <ArtworkBaseListbox
                    v-model="selectedTimeType"
                    :items="timeTypes"
                    by="id"
                    option-label="value"
                    :enable-search="false"
                    class="sm:col-span-3"
                />
            </div>
        </form>
        <template #footer>
            <BaseUIButton
                label="Cancel"
                is-cancel-button
                hide-icon
                :disabled="processing"
                @click="closeModal"
            />
            <BaseUIButton
                type="submit"
                form="editSeriesEventsForm"
                label="Edit all series events"
                variant="primary"
                icon="IconDeviceFloppy"
                :processing="processing"
            />
        </template>
    </ArtworkBaseModal>
</template>

<script setup>
import {computed, ref} from "vue";
import {useI18n} from "vue-i18n";
import axios from "axios";
import ArtworkBaseModal from "@/Artwork/Modals/ArtworkBaseModal.vue";
import ArtworkBaseListbox from "@/Artwork/Listbox/ArtworkBaseListbox.vue";
import BaseInput from "@/Artwork/Inputs/BaseInput.vue";
import BaseUIButton from "@/Artwork/Buttons/BaseUIButton.vue";
import {ticketingMoveHeaders} from "@/Composeables/useTicketingMove.js";

defineOptions({name: 'EditSeriesEventsModal'})

const {t} = useI18n();

const props = defineProps({
    event: {type: Object, required: true},
    rooms: {type: [Object, Array], required: true},
});

const emits = defineEmits(["close"]);

const noRoomDisplacement = {id: null, name: t("No room displacement")};

const roomOptions = computed(() => [
    noRoomDisplacement,
    ...Object.values(props.rooms ?? {}),
]);

const calculationTypes = [
    {id: 1, type: "+"},
    {id: 2, type: "-"},
];

const timeTypes = [
    {id: 1, value: t("Hour(s)")},
    {id: 2, value: t("Day(s)")},
    {id: 3, value: t("Week(s)")},
    {id: 4, value: t("Month(s)")},
    {id: 5, value: t("Year(s)")},
];

const selectedCalculationType = ref(calculationTypes[0]);
const selectedTimeType = ref(timeTypes[0]);
const selectedRoom = ref(noRoomDisplacement);
const editValue = ref(0);
const processing = ref(false);

const closeModal = () => {
    if (processing.value) {
        return;
    }
    emits("close");
};

const saveSeriesEdit = async () => {
    if (processing.value) {
        return;
    }
    processing.value = true;
    const headers = await ticketingMoveHeaders([props.event.id], {withSeries: true});
    if (!headers) {
        processing.value = false;
        return;
    }

    axios
        .patch(route("events.series.update", props.event.id), {
            newRoomId: selectedRoom.value?.id ?? null,
            calculationType: selectedCalculationType.value?.id ?? calculationTypes[0].id,
            value: editValue.value,
            type: selectedTimeType.value?.id ?? timeTypes[0].id,
        }, {headers})
        .then(() => {
            emits("close");
            location.reload();
        })
        .catch((error) => {
            processing.value = false;
            console.error("Series edit save failed:", error);
        });
};
</script>
