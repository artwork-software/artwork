<template>
    <ArtworkBaseModal
        :title="modalTitle"
        :description="periodText"
        modal-size="sm:max-w-lg"
        @close="closeModal(false)"
    >
        <dl class="grid grid-cols-[auto_1fr] gap-x-4 gap-y-2 text-sm">
            <dt class="text-text-subtle">{{ $t('Room') }}</dt>
            <dd class="min-w-0 truncate font-semibold text-text">{{ room?.name ?? '–' }}</dd>

            <dt class="text-text-subtle">{{ $t('Event type') }}</dt>
            <dd class="flex min-w-0 items-center gap-x-2 text-text">
                <span
                    v-if="eventType?.hex_code"
                    class="size-3 shrink-0 rounded-full"
                    :style="{ backgroundColor: eventType.hex_code }"
                />
                <span class="truncate">{{ eventType?.name ?? '–' }}</span>
            </dd>

            <template v-if="request?.eventName">
                <dt class="text-text-subtle">{{ $t('Event name') }}</dt>
                <dd class="min-w-0 truncate text-text">{{ request.eventName }}</dd>
            </template>

            <template v-if="request?.project_id">
                <dt class="text-text-subtle">{{ $t('Project') }}</dt>
                <dd class="min-w-0 truncate text-text">{{ project?.name ?? '–' }}</dd>
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
                v-if="type === 'accept'"
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

defineOptions({name: 'AnswerEventRequestComponent'})

const props = defineProps({
    type: {type: String, default: null},
    request: {type: Object, default: null},
    rooms: {type: Array, default: () => []},
    projects: {type: Array, default: () => []},
    eventTypes: {type: Array, default: () => []},
})

const emit = defineEmits(['closed'])

const {t} = useI18n()

// Titel wie bisher aus vorhandenen Schlüsseln zusammengesetzt
const modalTitle = computed(() => `${t('Room request')} ${t(props.type === 'accept' ? 'Confirm' : 'Reject')}`)

const room = computed(() => props.rooms?.find((roomItem) => roomItem.id === props.request?.room_id))
const eventType = computed(() => props.eventTypes?.find((eventTypeItem) => eventTypeItem.id === props.request?.event_type_id))
const project = computed(() => props.projects?.find((projectItem) => projectItem.id === props.request?.project_id))

const periodText = computed(() => {
    const start = formatDate(props.request?.start_time)
    const end = formatDate(props.request?.end_time)
    if (!start && !end) {
        return ''
    }
    return `${start ?? ''} - ${end ?? ''}`
})

function closeModal(answered) {
    emit('closed', answered)
}

function formatDate(isoDate) {
    if (isoDate?.split('T').length > 1) {
        const [date, time] = isoDate.split('T')
        return date.substring(8, 10) + '.' + date.substring(5, 7) + '.' + date.substring(0, 4) + ', ' + time.substring(0, 5)
    }
    if (isoDate?.split(' ').length > 1) {
        const [date, time] = isoDate.split(' ')
        return date.substring(8, 10) + '.' + date.substring(5, 7) + '.' + date.substring(0, 4) + ', ' + time.substring(0, 5)
    }
    return null
}
</script>
