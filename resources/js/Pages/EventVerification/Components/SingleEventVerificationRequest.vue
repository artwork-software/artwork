<template>
    <div class="min-w-0">
        <div class="flex items-start gap-x-3">
            <p class="text-sm/6 font-semibold text-gray-900">{{ eventVerification?.event?.eventName }}</p>
            <p :class="[statuses[eventVerification.status], 'mt-0.5 rounded-md px-1.5 py-0.5 text-xs font-medium whitespace-nowrap ring-1 ring-inset']" class="first-letter:capitalize">{{ $t(eventVerification.status) }}</p>
        </div>
        <div class="mt-1 flex items-center gap-x-2 text-xs/5 text-gray-500">
            <p class="truncate">{{ $t('created at')}} <span class="font-lexend">{{ eventVerification.created_at }}</span></p>
        </div>
    </div>
    <div class="flex flex-none items-center gap-x-4">
        <div>
            <SmallFormButton @click="approveRequest" v-if="eventVerification.status === 'pending'" class="!bg-green-600 hover:!bg-green-800 capitalize text-xs font-lexend">
                <component is="IconCheckbox" class="size-4" aria-hidden="true" />
                {{ $t('Approve') }}
            </SmallFormButton>
        </div>
        <div>
            <SmallFormButton @click="rejectRequest" v-if="eventVerification.status === 'pending'" class="!bg-red-500 hover:!bg-red-800 capitalize text-xs font-lexend">
                <component is="IconBan" class="size-4" aria-hidden="true" />
                {{ $t('Reject') }}
            </SmallFormButton>
        </div>
    </div>
</template>

<script setup>

import SmallFormButton from "@/Components/Buttons/SmallFormButton.vue";
import {router} from "@inertiajs/vue3";

const props = defineProps({
    eventVerification: {
        type: Object,
        required: true
    }
})

const statuses = {
    approved: 'text-green-700 bg-green-50 ring-green-600/20',
    pending: 'text-gray-600 bg-gray-50 ring-gray-500/10',
    rejected: 'text-red-800 bg-red-50 ring-red-600/20',
}

const approveRequest = () => {
    router.post(route('event-verifications.approved', props.eventVerification.id), {}, {
        preserveScroll: true
    })
}

const rejectRequest = () => {
    router.post(route('event-verifications.rejected', props.eventVerification.id), {}, {
        preserveScroll: true
    })
}

</script>

<style scoped>

</style>