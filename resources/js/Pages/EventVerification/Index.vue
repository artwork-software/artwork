<template>
    <AppLayout>

        <div class="ml-14 pt-10 pb-20 max-w-7xl w-full">

            <div class="">
               <TinyPageHeadline
                   :title="$t('Event Verifications')"
                   :description="$t('Manage event verifications. You can add, edit, or delete event verifications here.')"
               />
            </div>


            <div class="my-10">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                    <CardStatusEventVerification @click="setFilterTo('pending')" :is-active="filterVerificationRequest === 'pending'" :count="counts.pending" icon="IconCalendarTime" color="text-gray-500" :label="$t('pending')" />
                    <CardStatusEventVerification @click="setFilterTo('approved')" :is-active="filterVerificationRequest === 'approved'" :count="counts.approved" icon="IconCalendarCheck" color="text-green-500" :label="$t('approved')" />
                    <CardStatusEventVerification @click="setFilterTo('rejected')" :is-active="filterVerificationRequest === 'rejected'" :count="counts.rejected" icon="IconCalendarX" color="text-red-500" :label="$t('rejected')" />
                    <CardStatusEventVerification :count="counts.requests" icon="IconCalendarQuestion" color="text-orange-500" :label="$t('requested')" />
                </div>
            </div>

            <div class="pb-10">
                <TinyPageHeadline
                    :title="$t('Event Verifications')"
                    :description="$t('Manage the event checks. You can approve or reject event checks here.')"
                />
                <div v-if="filteredEventVerifications.length > 0">
                    <ul role="list" class="divide-y divide-gray-100">
                        <li v-for="eventVerification in filteredEventVerifications" :key="eventVerification.id" class="flex items-center justify-between gap-x-6 py-5">
                            <SingleEventVerificationRequest :event-verification="eventVerification" />
                        </li>
                    </ul>
                    <BasePaginator property-name="eventVerifications" :entities="eventVerifications" use-custom-url-params />
                </div>
                <div v-else class="mt-3">
                    <BaseAlertComponent message="No event verification requests found" type="info" use-translation />
                </div>


            </div>


            <div class="border-t border-dashed border-gray-200 pt-10">
                <TinyPageHeadline
                    :title="$t('My verification Requests')"
                    :description="$t('Manage your event verification requests. You can add, edit, or delete your event verification requests here.')"
                />
                <div v-if="myRequests.data.length > 0">
                    <ul role="list" class="divide-y divide-gray-100">
                        <li v-for="myRequest in myRequests.data" :key="myRequest.id" class="flex items-center justify-between gap-x-6 py-5">
                            <SingleMyEventVerificationRequests :my-request="myRequest"  />
                        </li>
                    </ul>

                    <BasePaginator property-name="myRequests" :entities="myRequests" use-custom-url-params />
                </div>
                <div v-else class="mt-3">
                    <BaseAlertComponent message="No event verification requests found" type="info" use-translation />
                </div>


            </div>
        </div>
    </AppLayout>
</template>

<script setup>

import AppLayout from "@/Layouts/AppLayout.vue";
import TinyPageHeadline from "@/Components/Headlines/TinyPageHeadline.vue";
import CardStatusEventVerification from "@/Pages/EventVerification/Components/CardStatusEventVerification.vue";
import SingleEventVerificationRequest from "@/Pages/EventVerification/Components/SingleEventVerificationRequest.vue";
import SingleMyEventVerificationRequests
    from "@/Pages/EventVerification/Components/SingleMyEventVerificationRequests.vue";
import BasePaginator from "@/Components/Paginate/BasePaginator.vue";
import {computed, ref} from "vue";
import BaseAlertComponent from "@/Components/Alerts/BaseAlertComponent.vue";


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

const filterVerificationRequest = ref('all');

// filter eventVerifications.data by filterVerificationRequest
const filteredEventVerifications = computed(() => {
    if (filterVerificationRequest.value === 'all') {
        return props.eventVerifications.data;
    }
    return props.eventVerifications.data.filter(eventVerification => eventVerification.status === filterVerificationRequest.value);
});


const setFilterTo = (status) => {
    if ( filterVerificationRequest.value === status ) {
        filterVerificationRequest.value = 'all';
    } else {
        filterVerificationRequest.value = status;
    }
}
</script>

<style scoped>
</style>