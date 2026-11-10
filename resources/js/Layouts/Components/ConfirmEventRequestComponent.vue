<template>
    <ArtworkBaseModal
        v-if="showModal"
        :title="modalTitle"
        :description="periodText"
        modal-size="sm:max-w-lg"
        @close="closeModal(false)"
    >
        <dl class="grid grid-cols-[auto_1fr] gap-x-4 gap-y-2 text-sm">
            <dt class="text-text-subtle">{{ $t('Room') }}</dt>
            <dd class="min-w-0 truncate font-semibold text-text">{{ requestToApprove?.room?.name ?? '–' }}</dd>

            <dt class="text-text-subtle">{{ $t('Event type') }}</dt>
            <dd class="min-w-0 truncate text-text">{{ requestToApprove?.eventType?.name ?? '–' }}</dd>

            <template v-if="requestToApprove?.eventName">
                <dt class="text-text-subtle">{{ $t('Event name') }}</dt>
                <dd class="min-w-0 truncate text-text">{{ requestToApprove.eventName }}</dd>
            </template>

            <template v-if="requestToApprove?.project?.name">
                <dt class="text-text-subtle">{{ $t('Project') }}</dt>
                <dd class="min-w-0 truncate text-text">{{ requestToApprove.project.name }}</dd>
            </template>
        </dl>
        <template #footer>
            <BaseUIButton
                label="No, not really"
                is-cancel-button
                hide-icon
                @click="closeModal(false)"
            />
            <BaseUIButton
                v-if="isConfirm"
                label="Confirm"
                variant="primary"
                icon="IconCheck"
                @click="closeModal(true)"
            />
            <BaseUIButton
                v-else
                label="Reject"
                variant="danger"
                icon="IconX"
                @click="closeModal(true)"
            />
        </template>
    </ArtworkBaseModal>
</template>

<script setup>
import {computed} from 'vue'
import {useI18n} from 'vue-i18n'
import ArtworkBaseModal from '@/Artwork/Modals/ArtworkBaseModal.vue'
import BaseUIButton from '@/Artwork/Buttons/BaseUIButton.vue'

defineOptions({name: 'ConfirmationComponent'})

const props = defineProps({
    showModal: {type: Boolean, default: false},
    requestToApprove: {type: Object, default: null},
    // 'warning' = Anfrage bestätigen, sonst ablehnen
    mode: {type: String, default: null},
})

const emit = defineEmits(['closed'])

const {t} = useI18n()

const isConfirm = computed(() => props.mode === 'warning')

// Titel wie bisher aus vorhandenen Schlüsseln zusammengesetzt
const modalTitle = computed(() => `${t('Room request')} ${t(isConfirm.value ? 'Confirm' : 'Reject')}`)

const periodText = computed(() => {
    if (!props.requestToApprove?.start && !props.requestToApprove?.end) {
        return ''
    }
    return `${props.requestToApprove?.start ?? ''} - ${props.requestToApprove?.end ?? ''}`
})

function closeModal(confirmed) {
    emit('closed', confirmed)
}
</script>
