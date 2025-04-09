<template>
    <AppLayout>

        <div>
            <div class="bg-lightBackgroundGray py-3 -mx-5 font-lexend">
                <div class="ml-20 mr-10 flex items-center justify-between">
                    <div>
                        <TinyPageHeadline
                            :title="$t('Event Verifications')"
                            :description="$t('Manage the event checks. You can approve or reject event checks here.')"
                        />
                    </div>
                    <div>
                        <div class="flex items-center gap-x-3">
                            <span :class="[statuses['pending'], 'rounded-md px-3 py-1.5 text-sm font-lexend whitespace-nowrap ring-1 ring-inset', filterVerificationRequest === 'pending' ? 'font-bold' : '']" class="cursor-pointer"  @click="setFilterTo('pending')">{{ counts.pending }} {{ $t('pending') }}</span>
                            <span :class="[statuses['approved'], 'rounded-md px-3 py-1.5 text-sm font-lexend whitespace-nowrap ring-1 ring-inset', filterVerificationRequest === 'approved' ? 'font-bold' : '']" class="cursor-pointer" @click="setFilterTo('approved')">{{ counts.approved }} {{ $t('approved') }}</span>
                            <span :class="[statuses['rejected'], 'rounded-md px-3 py-1.5 text-sm font-lexend whitespace-nowrap ring-1 ring-inset', filterVerificationRequest === 'rejected' ? 'font-bold' : '']" class="cursor-pointer" @click="setFilterTo('rejected')">{{ counts.rejected }} {{ $t('rejected') }}</span>
                            <span :class="[statuses['pending'], 'rounded-md px-3 py-1.5 text-sm font-lexend whitespace-nowrap ring-1 ring-inset']" v-if="filterVerificationRequest !== ''" class="cursor-pointer" @click="setFilterTo('')">{{ $t('Reset') }}</span>
                        </div>
                        <p class="text-xs mt-1 text-gray-500 font-thin text-center">
                            {{ $t('Click on the status to filter the event verification requests') }}
                        </p>
                    </div>
                </div>
            </div>

            <div class="ml-14 pt-5 pb-20 max-w-7xl w-full">

                <div class="" v-if="eventVerifications.data.length > 0">
                    <div class="mt-8 flow-root">
                        <div class="-mx-4 -my-2 overflow-x-auto sm:-mx-6 lg:-mx-8">
                            <div class="inline-block min-w-full py-2 align-middle sm:px-6 lg:px-8">
                                <table class="min-w-full divide-y divide-gray-300">
                                    <thead>
                                        <tr class="divide-x divide-gray-200">
                                            <th scope="col" class="py-3.5 pr-3 pl-4 text-left text-sm font-semibold text-gray-900 sm:pl-3">{{ $t('Event Data') }}</th>
                                            <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">{{ $t('Requested at') }}</th>
                                            <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">{{ $t('Requested by') }}</th>
                                            <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">{{ $t('Actions') }} </th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-200 bg-white">
                                        <tr v-for="eventVerification in eventVerifications.data" :key="eventVerification.email"  class="even:bg-gray-50 divide-x divide-gray-200">
                                            <SingleEventVerificationRequest :event-verification="eventVerification" />
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    <div class="mt-4">
                        <BasePaginator property-name="eventVerifications" :entities="eventVerifications" use-custom-url-params />
                    </div>
                </div>

                <div v-else class="mt-5">
                    <BaseAlertComponent message="No event verification requests found" type="info" use-translation />
                </div>
            </div>
        </div>





        <div class="ml-14 pt-10 pb-20 max-w-7xl w-full">

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
import {router, usePage} from "@inertiajs/vue3";


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

const filterVerificationRequest = ref(usePage().props.urlParameters.filterVerificationRequest ?? '');

const statuses = {
    approved: 'text-green-700 bg-green-50 ring-green-600/20',
    pending: 'text-gray-600 bg-gray-50 ring-gray-500/10',
    rejected: 'text-red-800 bg-red-50 ring-red-600/20',
}

const setFilterTo = (status) => {
    if ( filterVerificationRequest.value === status ) {
        filterVerificationRequest.value = '';
    } else {
        filterVerificationRequest.value = status;
    }

    router.reload({
        preserveScroll: true,
        preserveState: false,
        data: {
            page: 1,
            filterVerificationRequest: filterVerificationRequest.value
        }
    })
}
</script>

<style scoped>
</style>