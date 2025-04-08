<template>
    <AppLayout>

        <div class="ml-14 pt-10 max-w-7xl w-full">

            <div class="">
               <TinyPageHeadline
                   :title="$t('Event Verifications')"
                   :description="$t('Manage event verifications. You can add, edit, or delete event verifications here.')"
               />
            </div>


            <div class="my-10">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                    <CardStatusEventVerification :count="counts.requests" icon="IconCalendarQuestion" color="text-orange-500" :label="$t('requested')" />
                    <CardStatusEventVerification :count="counts.pending" icon="IconCalendarTime" color="text-gray-500" :label="$t('pending')" />
                    <CardStatusEventVerification :count="counts.approved" icon="IconCalendarCheck" color="text-green-500" :label="$t('approved')" />
                    <CardStatusEventVerification :count="counts.rejected" icon="IconCalendarX" color="text-red-500" :label="$t('rejected')" />
                </div>
            </div>

            <div>
                <TinyPageHeadline
                    :title="$t('Event Verifications')"
                    :description="$t('Manage your event verifications. You can add, edit, or delete your event verifications here.')"
                />
                <ul role="list" class="divide-y divide-gray-100">
                    <li v-for="eventVerification in eventVerifications" :key="eventVerification.id" class="flex items-center justify-between gap-x-6 py-5">
                        <SingleEventVerificationRequest :event-verification="eventVerification" />
                    </li>
                </ul>
            </div>


            <div class="border-t border-dashed border-gray-200 pt-4">
                <TinyPageHeadline
                    :title="$t('My verification Requests')"
                    :description="$t('Manage your event verification requests. You can add, edit, or delete your event verification requests here.')"
                />
                <ul role="list" class="divide-y divide-gray-100">
                    <li v-for="myRequest in myRequests" :key="myRequest.id" class="flex items-center justify-between gap-x-6 py-5">
                        <div class="min-w-0">
                            <div class="flex items-start gap-x-3">
                                <p class="text-sm/6 font-semibold text-gray-900">{{ myRequest.eventName }}</p>
                            </div>
                            <div class="mt-1 flex items-center gap-x-2 text-xs/5 text-gray-500">
                                <p class="truncate">{{ $t('created at')}} <span class="font-lexend">{{ myRequest.created_at }}</span></p>
                            </div>
                        </div>
                        <div class="flex flex-none items-center gap-x-4">
                            <div class="flex items-center gap-x-4">
                                <dd v-for="commenter in getAllVerifierInSingleRequest(myRequest.verifications)" :key="commenter.id">
                                    <div class="flex items-center gap-x-2">
                                        <UserPopoverTooltip :user="commenter" height="8" width="8" />
                                        <div>
                                            <p class="text-xs font-lexend font-semibold text-gray-900">{{ commenter.full_name }}</p>
                                            <p :class="[statuses[commenter.status], 'mt-1 w-full rounded-md text-center px-1.5 py-0.5 text-[9px] font-medium whitespace-nowrap ring-1 ring-inset']" class="first-letter:capitalize">{{ $t(commenter.status) }}</p>
                                        </div>
                                    </div>
                                </dd>
                            </div>
                        </div>

                        <pre class="hidden">
                            {{  getAllVerifierInSingleRequest(myRequest.verifications) }}
                        </pre>
                    </li>
                </ul>
            </div>
        </div>


        <pre>
            {{myRequests}}
            {{eventVerifications}}
            {{counts}}
        </pre>
    </AppLayout>
</template>

<script setup>

import AppLayout from "@/Layouts/AppLayout.vue";
import TinyPageHeadline from "@/Components/Headlines/TinyPageHeadline.vue";
import CardStatusEventVerification from "@/Pages/EventVerification/Components/CardStatusEventVerification.vue";
import SingleEventVerificationRequest from "@/Pages/EventVerification/Components/SingleEventVerificationRequest.vue";
import UserPopoverTooltip from "@/Layouts/Components/UserPopoverTooltip.vue";

const props = defineProps({
    eventVerifications: {
        type: Object,
        required: true
    },
    myRequests: {
        type: Object,
        required: true
    },
    counts: {
        type: Object,
        required: true
    }
})

const statuses = {
    approved: 'text-green-700 bg-green-50 ring-green-600/20',
    pending: 'text-gray-600 bg-gray-50 ring-gray-500/10',
    rejected: 'text-red-800 bg-red-50 ring-red-600/20',
}

const getAllVerifierInSingleRequest = (verifications) => {
    let verifiers = [];
    verifications.forEach((verification) => {
        if (!verifiers.includes(verification.verifier)) {
            let verifier = {
                ...verification.verifier,
                status: verification.status,
            }
            verifiers.push(verifier);
        }
    });
    return verifiers;
}

</script>

<style scoped>

</style>