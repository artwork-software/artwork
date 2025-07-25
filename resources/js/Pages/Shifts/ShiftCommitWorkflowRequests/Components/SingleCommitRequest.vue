<template>
    <div class="flex justify-between items-center mb-4">
        <div class="flex items-center gap-3">
            <UserPopoverTooltip :user="request.requested_by" />
            <div>
                <h2 class="text-lg font-bold text-gray-900">
                    {{ request.requested_by.full_name }}
                </h2>
                <p class="text-xs text-gray-500">
                    {{ request.requested_by.position }} | {{ request.requested_by.business }}
                </p>
            </div>
        </div>

        <span
            class="inline-flex items-center gap-1 capitalize border"
            :class="[
                            'text-xs px-3 py-1 rounded-full',
                            request.status === 'pending' ? 'bg-yellow-50 text-yellow-800 border-yellow-200' :
                            request.status === 'approved' ? 'bg-green-50 text-green-800 border-green-200' :
                            'bg-red-50 text-red-800 border-red-200'
                        ]"
        >
                        {{ $t(request.status) }}
                    </span>
    </div>

    <div class="text-sm text-gray-700 mb-4">
        <p><strong>Zeitraum:</strong> {{ formatDate(request.start_date) }} – {{ formatDate(request.end_date) }}</p>
        <p v-if="request.reason"><strong>Grund:</strong> {{ request.reason }}</p>
    </div>

    <div class="flex justify-end gap-3">
        <ArtworkBaseModalButton
            variant="success"
            @click="approveRequest"
            :disabled="request.status !== 'pending'"
        >
            {{ $t('Approve') }}
        </ArtworkBaseModalButton>
        <ArtworkBaseModalButton
            variant="danger"
            @click="showDeclineShiftCommitRequestModal = true"
            :disabled="request.status !== 'pending'"
        >
            Ablehnen
        </ArtworkBaseModalButton>
    </div>

    <DeclineShiftCommitRequest
        :request-id="request.id"
        v-if="showDeclineShiftCommitRequestModal"
        @close="showDeclineShiftCommitRequestModal = false"
    />
</template>

<script setup>

import UserPopoverTooltip from "@/Layouts/Components/UserPopoverTooltip.vue";
import ArtworkBaseModalButton from "@/Artwork/Buttons/ArtworkBaseModalButton.vue";
import {router} from "@inertiajs/vue3";
import DeclineShiftCommitRequest
    from "@/Pages/Shifts/ShiftCommitWorkflowRequests/Components/DeclineShiftCommitRequest.vue";
import {ref} from "vue";

const props = defineProps({
    request: {
        type: Object,
        required: true
    }
})

const showDeclineShiftCommitRequestModal = ref(false)

const formatDate = (dateString) => {
    return new Date(dateString).toLocaleDateString('de-DE', {
        year: 'numeric',
        month: '2-digit',
        day: '2-digit'
    });
}

const approveRequest = () => {
    // Logic to approve the request
    router.patch(route('shifts.commit-requests.approve', props.request.id), {
    })
};

</script>

<style scoped>

</style>
