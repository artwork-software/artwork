<template>
    <BaseCard elevation="raised" class="w-full">
        <div class="flex items-stretch gap-x-3 min-w-full w-full h-full p-4">
            <div class="p-1 rounded-lg w-1" :class="statuses[eventVerification.status]"></div>
            <div class="w-full">
                <p class="text-sm font-lexend font-semibold text-text" :style="{color: eventVerification?.event?.event_type?.hex_code}">
                    {{ eventVerification?.event?.event_type?.abbreviation }}: {{ eventVerification?.event?.eventName ?? eventVerification?.event?.project?.name }}
                </p>
                <p class="mt-1 flex items-center gap-x-1 text-xs text-text-subtle">
                    <span class="font-lexend font-bold">{{ $t('Start') }}:</span>
                    <span class="font-lexend">{{ eventVerification?.event?.start_time }}</span>
                    <span class="font-lexend font-bold">{{ $t('End') }}:</span>
                    <span class="font-lexend">{{ eventVerification?.event?.end_time }}</span>
                </p>
                <p class="mt-1 flex items-center gap-x-1 text-xs text-text-subtle">
                    <span class="font-lexend font-bold">{{ $t('Room') }}:</span>
                    <span class="font-lexend">{{ eventVerification?.event?.room?.name }}</span>
                </p>
                <div class="flex items-center justify-between mt-3">
                    <div>
                        <BaseUIButton variant="secondary" hide-icon @click="openPlanningCalendarWithEventId">
                            <span class="flex items-center gap-x-2">
                                <component :is="IconCalendar" class="size-4" aria-hidden="true" />
                                {{ $t('Open in Calendar') }}
                            </span>
                        </BaseUIButton>
                    </div>
                    <div>
                        <BaseUIButton variant="secondary" hide-icon @click="approveRequest" v-if="eventVerification.status === 'pending'" class="!bg-success hover:!bg-success capitalize text-xs font-lexend">
                            <span class="flex items-center gap-x-2">
                                <component :is="IconCheckbox" class="size-4" aria-hidden="true" />
                                {{ $t('Approve') }}
                            </span>
                        </BaseUIButton>
                    </div>
                    <div>
                        <BaseUIButton variant="secondary" hide-icon @click="showRejectEventVerificationRequestModal = true" v-if="eventVerification.status === 'pending'" class="!bg-danger hover:!bg-danger capitalize text-xs font-lexend">
                            <span class="flex items-center gap-x-2">
                                <component :is="IconBan" class="size-4" aria-hidden="true" />
                                {{ $t('Reject') }}
                            </span>
                        </BaseUIButton>
                    </div>
                </div>
            </div>
        </div>
    </BaseCard>
    <!--<td class="py-4 pr-3 pl-4 text-sm font-medium whitespace-nowrap text-text sm:pl-3">
        <div>
            <div class="flex items-start gap-x-1">
                <component is="IconCalendar" class="size-6 text-text-subtle hover:text-accent-600 duration-200 ease-in-out cursor-pointer" @click="openPlanningCalendarWithEventId" aria-hidden="true" />
                <p class="text-sm/6 font-semibold text-text">{{ eventVerification?.event?.eventName }}</p>
            </div>
            <p class="mt-1 flex items-center gap-x-1 text-[10px] text-text-subtle">
                <span class="font-lexend font-bold">{{ $t('Start') }}:</span>
                <span class="font-lexend">{{ eventVerification?.event?.start_time }}</span>
                <span class="font-lexend font-bold">{{ $t('End') }}:</span>
                <span class="font-lexend">{{ eventVerification?.event?.end_time }}</span>
                <span class="font-lexend font-bold">{{ $t('Room') }}:</span>
                <span class="font-lexend">{{ eventVerification?.event?.room?.name }}</span>
            </p>
        </div>
    </td>
    <td class="px-3 py-4 text-sm whitespace-nowrap text-text-subtle">{{ eventVerification.created_at }}</td>
    <td class="px-3 py-4 text-sm whitespace-nowrap text-text-subtle">
        <div class="flex items-center gap-x-2">
            <UserPopoverTooltip :user="eventVerification.requester" height="7" width="7" />
            <span class="font-lexend font-bold">
                {{ eventVerification.requester.full_name }}
            </span>
        </div>
    </td>
    <td class="px-3 py-4 text-sm whitespace-nowrap text-text-subtle">
        <div class="flex items-center justify-center gap-x-2">
            <div>
                <BaseUIButton variant="primary" hide-icon @click="approveRequest" v-if="eventVerification.status === 'pending'" class="!bg-success hover:!bg-success capitalize text-xs font-lexend">
                    <component is="IconCheckbox" class="size-4" aria-hidden="true" />
                </BaseUIButton>
            </div>
            <div>
                <BaseUIButton variant="primary" hide-icon @click="showRejectEventVerificationRequestModal = true" v-if="eventVerification.status === 'pending'" class="!bg-danger hover:!bg-danger capitalize text-xs font-lexend">
                    <component is="IconBan" class="size-4" aria-hidden="true" />
                </BaseUIButton>
            </div>
        </div>

        <p v-if="eventVerification.status !== 'pending'" :class="[statuses[eventVerification.status], 'mt-0.5 rounded-md px-1.5 py-0.5 text-xs text-center font-medium whitespace-nowrap ring-1 ring-inset']" class="first-letter:capitalize">{{ $t(eventVerification.status) }}</p>
    </td>-->

    <RejectEventVerificationRequestModal
        v-if="showRejectEventVerificationRequestModal"
        :event-verification="eventVerification"
        @close="showRejectEventVerificationRequestModal = false"
    />
</template>

<script setup>

import {router} from "@inertiajs/vue3";
import UserPopoverTooltip from "@/Layouts/Components/UserPopoverTooltip.vue";
import {defineAsyncComponent, ref} from "vue";
import BaseCard from "@/Artwork/Cards/BaseCard.vue";
import BaseUIButton from "@/Artwork/Buttons/BaseUIButton.vue";
import {IconBan, IconCalendar, IconCheckbox} from "@tabler/icons-vue";

const props = defineProps({
    eventVerification: {
        type: Object,
        required: true
    }
})

const statuses = {
    approved: 'text-success bg-success-surface ring-success-border',
    pending: 'text-text-muted bg-surface-sunken border-border-subtle',
    rejected: 'text-danger bg-danger-surface ring-danger-border',
}

const showRejectEventVerificationRequestModal = ref(false)

const RejectEventVerificationRequestModal = defineAsyncComponent({
    loader: () => import('@/Pages/EventVerification/Components/RejectEventVerificationRequestModal.vue'),
    delay: 200,
    timeout: 3000,
})

const approveRequest = () => {
    router.post(route('event-verifications.approved', props.eventVerification.id), {}, {
        preserveScroll: true,
        preserveState: false,
    })
}

// Zeitraum und Ziel (Planungskalender bzw. Kalender) bestimmt der Server – vorher wurde hier start_time
// ("25. Oct 2025 09:00") per Regex zerlegt und bei unbekanntem Format gar nicht navigiert
const openPlanningCalendarWithEventId = () => {
    router.get(route('event-verifications.redirect-to-calendar', props.eventVerification.event.id))
};


</script>

<style scoped>

</style>
