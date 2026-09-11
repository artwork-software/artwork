<template>
    <ArtworkBaseModal
        :title="isAccept ? 'Accept shift' : 'Decline shift'"
        :description="isAccept
            ? 'You can add an optional comment for the planners.'
            : 'You can add an optional comment explaining why you decline this shift.'"
        @close="$emit('close')"
    >
        <form @submit.prevent="submit" class="space-y-6">
            <div v-if="workerName" class="text-sm text-text">
                {{ isAccept
                    ? $t('Accept shift assignment for {name}?', { name: workerName })
                    : $t('Decline shift assignment for {name}?', { name: workerName }) }}
            </div>

            <BaseTextarea
                v-model="comment"
                rows="3"
                :label="$t('Comment (optional)')"
                id="shift-confirmation-response-comment"
                :maxlength="500"
            />

            <!-- Absage ändert die Planung nicht von selbst – Begründung bleibt optional -->
            <div
                v-if="!isAccept"
                class="flex items-start gap-2 rounded-lg border border-warning-border bg-warning-surface px-3 py-2 text-xs text-warning"
            >
                <PropertyIcon name="IconInfoCircle" class="h-4 w-4 shrink-0 mt-px" stroke-width="1.5" />
                <span v-if="workerName">{{ $t('The person remains scheduled until the plan is changed.') }}</span>
                <span v-else>{{ $t('You remain scheduled until the plan is changed.') }} {{ $t('The planners will be notified of your decline.') }}</span>
            </div>

            <div class="mt-4 flex justify-between gap-4">
                <BaseUIButton type="button" @click="$emit('close')" :label="$t('Cancel')" is-cancel-button />
                <BaseUIButton
                    type="submit"
                    :is-delete-button="!isAccept"
                    :label="isAccept ? $t('Accept shift') : $t('Decline shift')"
                />
            </div>
        </form>
    </ArtworkBaseModal>
</template>

<script setup>
import { computed, ref } from 'vue'
import ArtworkBaseModal from '@/Artwork/Modals/ArtworkBaseModal.vue'
import BaseTextarea from '@/Artwork/Inputs/BaseTextarea.vue'
import BaseUIButton from '@/Artwork/Buttons/BaseUIButton.vue'
import PropertyIcon from '@/Artwork/Icon/PropertyIcon.vue'

const props = defineProps({
    // 'accept' | 'decline' — steuert Texte und Button-Stil
    mode: { type: String, required: false, default: 'decline' },
    workerName: { type: String, required: false, default: null },
})

const emit = defineEmits(['close', 'submit'])

const isAccept = computed(() => props.mode === 'accept')
const comment = ref('')

const submit = () => {
    emit('submit', comment.value.trim() || null)
}
</script>
