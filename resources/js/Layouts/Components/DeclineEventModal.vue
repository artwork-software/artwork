<template>
    <ArtworkBaseModal
        title="Cancel booking"
        :description="periodText"
        modal-size="sm:max-w-xl"
        @close="close"
    >
        <div class="space-y-4">
            <div class="flex items-start justify-between gap-x-4">
                <div class="flex min-w-0 items-center gap-x-2">
                    <span
                        class="size-4 shrink-0 rounded-full"
                        :style="{ backgroundColor: eventTypes?.[requestToDecline?.eventTypeId]?.hex_code }"
                    />
                    <span class="truncate font-lexend text-sm font-bold text-text">
                        {{ requestToDecline?.eventTypeName }}<template v-if="requestToDecline?.eventName"> – {{ requestToDecline?.eventName }}</template>
                    </span>
                    <IconAdjustmentsAlt
                        v-if="requestToDecline?.occupancy_option"
                        class="size-4 shrink-0 text-text-subtle"
                        stroke-width="1.5"
                    />
                    <img v-if="requestToDecline?.audience" src="/Svgs/IconSvgs/icon_public.svg" class="size-4 shrink-0" alt=""/>
                    <img v-if="requestToDecline?.is_loud" src="/Svgs/IconSvgs/icon_loud.svg" class="size-4 shrink-0" alt=""/>
                </div>
                <div class="flex shrink-0 items-center gap-x-2 text-xs text-text-subtle">
                    {{ $t('Created by') }}
                    <UserPopoverTooltip
                        :user="requestToDecline?.created_by"
                        :id="requestToDecline?.created_by?.id ?? 'deletedUserTooltip'"
                        height="4"
                        width="4"
                    />
                </div>
            </div>

            <div class="space-y-1 text-sm text-text">
                <div class="font-semibold">
                    {{ requestToDecline?.roomName }}
                </div>
                <div v-if="requestToDecline?.project" class="flex items-center gap-x-2 text-xs text-text-subtle">
                    {{ $t('assigned to') }}
                    <span class="font-bold">{{ requestToDecline?.project?.name }}</span>
                </div>
                <div v-else class="text-xs text-text-subtle">
                    {{ $t('No project assignment') }}
                </div>
                <div v-if="requestToDecline?.description" class="text-xs text-text-subtle">
                    {{ $t('Event info') }} {{ requestToDecline?.description }}
                </div>
            </div>

            <BaseTextarea
                id="declineEventComment"
                v-model="comment"
                :label="$t('Would you like to enter a reason?')"
                :rows="4"
            />
        </div>
        <template #footer>
            <BaseUIButton
                label="No, not really"
                is-cancel-button
                hide-icon
                :disabled="processing"
                @click="close"
            />
            <BaseUIButton
                label="Cancellations"
                variant="danger"
                icon="IconX"
                :processing="processing"
                @click="declineRequest"
            />
        </template>
    </ArtworkBaseModal>
</template>

<script setup>
import {computed, ref} from 'vue'
import {router} from '@inertiajs/vue3'
import dayjs from 'dayjs'
import {IconAdjustmentsAlt} from '@tabler/icons-vue'
import ArtworkBaseModal from '@/Artwork/Modals/ArtworkBaseModal.vue'
import BaseTextarea from '@/Artwork/Inputs/BaseTextarea.vue'
import BaseUIButton from '@/Artwork/Buttons/BaseUIButton.vue'
import UserPopoverTooltip from '@/Layouts/Components/UserPopoverTooltip.vue'

const props = defineProps({
    requestToDecline: {type: Object, default: null},
    eventTypes: {type: [Object, Array], default: null},
})

const emit = defineEmits(['closed', 'declined'])

const comment = ref('')
const processing = ref(false)

const periodText = computed(() => {
    if (!props.requestToDecline?.start) {
        return ''
    }
    return dayjs(props.requestToDecline.start).format('DD.MM.YYYY HH:mm') + ' - ' +
        dayjs(props.requestToDecline.end).format('DD.MM.YYYY HH:mm')
})

const close = () => emit('closed', true)

const declineRequest = () => {
    processing.value = true
    router.put(route('events.decline', props.requestToDecline?.id), {
        comment: comment.value,
    }, {
        preserveScroll: true,
        onSuccess: () => {
            router.reload({only: ['eventsWithoutRoom']})
        },
        onFinish: () => {
            processing.value = false
            close()
            emit('declined')
        },
    })
}
</script>
