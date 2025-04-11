<template>
    <BaseEventVerificationLayout>
        <template #header>
            <PageTitle
                :title="$t('Event Verifications')"
                :description="$t('Manage the event checks. You can approve or reject event checks here.')"
            />
        </template>

        <template #body>
            <div class="grid grid-cols-6 grid-rows-5 gap-10">
                <div class="col-span-4">
                    <div v-if="eventVerifications.data.length > 0">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div v-for="eventVerification in eventVerifications.data" :key="eventVerification.id">
                                <SingleEventVerificationRequest :event-verification="eventVerification" />
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
                <div class="col-start-5 col-span-2">
                    <BaseCard>
                        <CardHeadline title="Filter" description="Filter your request according to your review decision." />
                        <div class="px-5 pb-5 space-y-4">
                            <div v-for="(status, key) in statuses">
                                <WhiteInnerCard @click="setFilterTo(key)"  classes="cursor-pointer">
                                    <div class="flex items-stretch gap-x-3 min-w-full w-full h-full">
                                        <div class="p-1 rounded-lg w-1" :class="status"></div>
                                        <div class="w-full">
                                            <p class="text-sm font-lexend font-semibold" :class="filterVerificationRequest === key ? '!text-artwork-buttons-create' : 'text-gray-900'">
                                                {{ $t(key) }}
                                            </p>

                                        </div>
                                    </div>
                                </WhiteInnerCard>
                            </div>
                            <WhiteInnerCard @click="setFilterTo('')"  classes="cursor-pointer" v-if="filterVerificationRequest !== ''">
                                <div class="flex items-stretch gap-x-3 min-w-full w-full h-full">
                                    <div class="p-1 rounded-lg w-1 bg-gray-500"></div>
                                    <div class="w-full">
                                        <p class="text-sm font-lexend font-semibold">
                                            {{ $t('Reset') }}
                                        </p>
                                    </div>
                                </div>
                            </WhiteInnerCard>
                        </div>
                    </BaseCard>
                </div>
            </div>
        </template>
    </BaseEventVerificationLayout>
</template>

<script setup>

import BaseEventVerificationLayout from "@/Pages/EventVerification/BaseEventVerificationLayout.vue";
import PageTitle from "@/Artwork/Titles/PageTitle.vue";
import {ref} from "vue";
import {router, usePage} from "@inertiajs/vue3";
import BaseCard from "@/Artwork/Cards/BaseCard.vue";
import CardHeadline from "@/Artwork/Cards/CardHeadline.vue";
import WhiteInnerCard from "@/Artwork/Cards/WhiteInnerCard.vue";
import BasePaginator from "@/Components/Paginate/BasePaginator.vue";
import SingleEventVerificationRequest from "@/Pages/EventVerification/Components/SingleEventVerificationRequest.vue";
import BaseAlertComponent from "@/Components/Alerts/BaseAlertComponent.vue";

const props = defineProps({
    eventVerifications: {
        type: Object,
        required: true
    },
})
const filterVerificationRequest = ref(usePage().props.urlParameters.filterVerificationRequest ?? '');

const statuses = {
    pending: 'text-gray-600 bg-gray-500 ring-gray-500/10',
    approved: 'text-green-700 bg-green-500 ring-green-600/20',
    rejected: 'text-red-800 bg-red-500 ring-red-600/20',
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