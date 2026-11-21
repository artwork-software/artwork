<template>
    <ArtworkBaseModal
        title="Answer room request"
        :description="summary"
        modal-size="sm:max-w-xl"
        @close="$emit('close')"
    >
        <div class="space-y-4">
            <p class="text-sm text-text-subtle">
                {{ $t('Confirming books the room firmly. Declining removes the event from the room; it stays in the calendar as an event without a room.') }}
            </p>
            <BaseTextarea
                id="bulkRoomRequestAnswerComment"
                v-model="comment"
                :label="$t('Comment (optional)')"
                :rows="3"
            />
        </div>
        <template #footer>
            <BaseUIButton
                label="Cancel"
                is-cancel-button
                hide-icon
                :disabled="processing"
                @click="$emit('close')"
            />
            <BaseUIButton
                label="Decline event"
                variant="danger"
                icon="IconX"
                :disabled="processing"
                @click="$emit('answer', {accepted: false, comment})"
            />
            <BaseUIButton
                label="Confirm event"
                variant="primary"
                icon="IconCheck"
                :processing="processing"
                :disabled="processing"
                @click="$emit('answer', {accepted: true, comment})"
            />
        </template>
    </ArtworkBaseModal>
</template>

<script setup>
import {computed, ref} from 'vue';
import dayjs from 'dayjs';
import ArtworkBaseModal from '@/Artwork/Modals/ArtworkBaseModal.vue';
import BaseTextarea from '@/Artwork/Inputs/BaseTextarea.vue';
import BaseUIButton from '@/Artwork/Buttons/BaseUIButton.vue';

const props = defineProps({
    // Zeile der Terminliste (Shape von BulkEventChanged::eventPayload)
    event: {type: Object, required: true},
    processing: {type: Boolean, default: false},
});

defineEmits(['answer', 'close']);

const comment = ref('');

// "Atelier 2 · Blocker, Probe · 20.11.2026 10:00 - 12:00" (ganztägig ohne Uhrzeit)
const summary = computed(() => {
    const event = props.event;
    const formatDay = (day) => (day ? dayjs(day).format('DD.MM.YYYY') : '');
    const start = [formatDay(event.day), event.start_time].filter(Boolean).join(' ');
    const endDay = event.end_day && event.end_day !== event.day ? formatDay(event.end_day) : '';
    const end = [endDay, event.end_time].filter(Boolean).join(' ');
    const title = [event.type?.name, event.name].filter(Boolean).join(', ');

    return [event.room?.name, title, [start, end].filter(Boolean).join(' - ')].filter(Boolean).join(' · ');
});
</script>
