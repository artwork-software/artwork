<template>
    <div class="min-w-0">
        <div class="flex items-start gap-x-3">
            <p class="text-sm/6 font-semibold text-gray-900">{{ myRequest?.eventName }}</p>
        </div>
        <div class="mt-1 flex items-center gap-x-2 text-xs/5 text-gray-500">
            <p class="mt-1 flex items-center gap-x-1 text-[10px] text-gray-500">
                <span class="font-lexend font-bold">{{ $t('Start') }}:</span>
                <span class="font-lexend">{{ myRequest?.start_time }}</span>
                <span class="font-lexend font-bold">{{ $t('End') }}:</span>
                <span class="font-lexend">{{ myRequest?.end_time }}</span>
                <span class="font-lexend font-bold">{{ $t('Room') }}:</span>
                <span class="font-lexend">{{ myRequest?.room?.name }}</span>
            </p>
        </div>
    </div>
    <div class="flex flex-none items-center gap-x-4 h-full">
        <div class="flex items-center gap-x-4 h-full">
            <div v-for="verifications in computedSplicedOrNotSplicedVerifications" class="flex items-center gap-x-4 px-3 py-2 rounded-lg h-full  ring-1 ring-inset" :class="getBackgroundColorByEventTypeVerificationModeAndWhoHasVerifyGroup(verifications)">
                <dd v-for="commenter in getAllVerifierInSingleRequest(verifications)" :key="commenter.id">
                    <div class="flex items-center gap-x-2">
                        <UserPopoverTooltip :user="commenter" height="8" width="8" />
                        <div>
                            <p class="text-xs font-lexend font-semibold text-gray-900">{{ commenter.full_name }}</p>
                            <p :class="[statuses[commenter.status], 'mt-1 w-full rounded-md text-center px-1.5 py-0.5 text-[9px] font-medium whitespace-nowrap ring-1 ring-inset']" class="first-letter:capitalize">{{ $t(commenter.status) }}</p>
                        </div>
                    </div>
                </dd>
            </div>
            <div v-if="computedSplicedOrNotSplicedVerifications.length >= 3 && spliceVerification">
                <div class="flex items-center gap-x-4 px-3 py-2 bg-gray-50 rounded-lg h-full cursor-pointer ring-1 ring-inset ring-gray-500/10" @click="spliceVerification = !spliceVerification">
                    <div>
                        <div class="text-xs font-lexend font-semibold text-gray-900">
                            {{ computedSplicedOrNotSplicedVerifications.length }} {{ $t('more') }}
                        </div>
                        <div class="mt-1.5">
                            <p class="text-xs font-lexend font-semibold text-gray-400">
                                {{ $t('Click to show more')}}
                            </p>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</template>

<script setup>

import UserPopoverTooltip from "@/Layouts/Components/UserPopoverTooltip.vue";
import {computed, ref} from "vue";

const props = defineProps({
    myRequest: {
        type: Object,
        required: true
    },
})

const spliceVerification = ref(true)
const groupedVerifications = ref(props.myRequest.grouped_verifications)


const computedSplicedOrNotSplicedVerifications = computed(() => {
    if (spliceVerification.value) {
        return groupedVerifications.value.slice(0, 2);
    } else {
        return groupedVerifications.value;
    }
})


const statuses = {
    approved: 'text-green-700 bg-green-50 ring-green-600/20',
    pending: 'text-gray-600 bg-gray-50 ring-gray-500/10',
    rejected: 'text-red-800 bg-red-50 ring-red-600/20',
}


const getAllVerifierInSingleRequest = (verifications) => {
    const mode = props.myRequest.event_type.verification_mode;

    if (mode === 'any') {
        const approved = verifications.find(v => v.status === 'approved');
        if (approved) {
            return [{
                ...approved.verifier,
                status: approved.status
            }];
        }
    }

    const verifiers = [];
    verifications.forEach(verification => {
        if (!verifiers.some(v => v.id === verification.verifier.id)) {
            verifiers.push({
                ...verification.verifier,
                status: verification.status
            });
        }
    });
    return verifiers;
}


const getBackgroundColorByEventTypeVerificationModeAndWhoHasVerifyGroup = (verifications) => {
    const mode = props.myRequest.event_type.verification_mode;

    const allApproved = verifications.every(v => v.status === 'approved');
    const anyApproved = verifications.some(v => v.status === 'approved');
    const allRejected = verifications.every(v => v.status === 'rejected');

    if (mode === 'any') {
        if (anyApproved) return statuses['approved'];
        if (allRejected) return statuses['rejected'];
        return statuses['pending'];
    }

    if (mode === 'all') {
        if (allApproved) return statuses['approved'];
        if (allRejected) return statuses['rejected'];
        return statuses['pending'];
    }

    return statuses['pending'];
};


</script>

<style scoped>

</style>