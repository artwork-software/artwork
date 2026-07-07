<template>
    <ArtworkBaseModal
        :title="$t('Delete compensation day')"
        :description="$t('Please provide a reason for deleting this compensation day.')"
        @close="$emit('close')"
    >
        <div class="space-y-4 text-sm">
            <div class="rounded-lg border border-zinc-200 bg-zinc-50/70 px-3 py-2.5">
                <div class="grid grid-cols-2 gap-2 text-xs text-zinc-700">
                    <div>
                        <span class="font-medium text-zinc-500">{{ $t('Value') }}:</span>
                        {{ compensationDay.value >= 1.0 ? $t('Full day (1.0)') : $t('Half day (0.5)') }}
                    </div>
                    <div>
                        <span class="font-medium text-zinc-500">{{ $t('Deadline') }}:</span>
                        {{ formatDate(compensationDay.deadline) }}
                    </div>
                </div>
            </div>

            <div>
                <BaseTextarea
                    id="delete_reason"
                    v-model="deleteReason"
                    :label="$t('Reason for deletion')"
                />
                <p v-if="showError && !deleteReason.trim()" class="mt-1 text-xs text-red-500">
                    {{ $t('Reason for deletion') }}
                </p>
            </div>

            <div class="flex justify-between pt-2 border-t border-zinc-100">
                <BaseUIButton
                    :label="$t('Cancel')"
                    is-cancel-button
                    @click="$emit('close')"
                />
                <BaseUIButton
                    :label="$t('Delete compensation day')"
                    is-delete-button
                    :disabled="submitting"
                    @click="submit"
                />
            </div>
        </div>
    </ArtworkBaseModal>
</template>

<script setup>
import { ref } from 'vue';
import { router } from '@inertiajs/vue3';
import ArtworkBaseModal from '@/Artwork/Modals/ArtworkBaseModal.vue';
import BaseTextarea from '@/Artwork/Inputs/BaseTextarea.vue';
import BaseUIButton from '@/Artwork/Buttons/BaseUIButton.vue';

const props = defineProps({
    compensationDay: { type: Object, required: true },
});

const emit = defineEmits(['close', 'deleted']);

const deleteReason = ref('');
const showError = ref(false);
const submitting = ref(false);

function formatDate(date) {
    if (!date) return '-';
    return new Date(date).toLocaleDateString('de-DE');
}

function submit() {
    showError.value = true;
    if (!deleteReason.value.trim()) return;

    submitting.value = true;
    router.delete(route('compensation-day-offs.delete', { compensationDayOff: props.compensationDay.id }), {
        data: { delete_reason: deleteReason.value },
        preserveScroll: true,
        onSuccess: () => emit('deleted'),
        onFinish: () => { submitting.value = false; },
    });
}
</script>
