<template>
    <BaseEventVerificationLayout>
        <template #header>
            <PageTitle
                :title="$t('My Verification Requests')"
                :description="$t('Manage your event verification requests. You can add, edit, or delete your event verification requests here.')"
            />
        </template>

        <template #body>
            <div class="grid grid-cols-6 grid-rows-5 gap-10">
                <div class="col-span-4">
                   <div v-if="myRequests.data.length > 0">
                       <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                           <div v-for="myRequest in myRequests.data" :key="myRequest.id">
                               <SingleMyEventVerificationRequests :my-request="myRequest" />
                           </div>
                       </div>

                       <div class="mt-5">
                           <BasePaginator property-name="myRequests" :entities="myRequests" use-custom-url-params />
                       </div>
                   </div>
                    <div v-else class="mt-3">
                        <BaseAlertComponent message="No event verification requests found" type="info" use-translation />
                    </div>
                </div>
                <div class="col-start-5 col-span-2">
                    <BaseCard>
                        <CardHeadline title="Planned Events" description="You can see all planned events here. You can also request a verification directly here." />
                        <div class="pb-5">
                            <div class="space-y-4 max-h-96 overflow-x-auto px-5 pb-5" v-if="plannedEvents.length > 0">
                                <div v-for="(event, index) in plannedEvents" :key="event.id">
                                    <SinglePlannedEventInVerificationPage :event="event" />
                                </div>
                            </div>
                            <div v-else class="px-5">
                                <BaseAlertComponent message="No planned events found" type="info" use-translation />
                            </div>
                        </div>
                    </BaseCard>
                </div>
            </div>
        </template>
    </BaseEventVerificationLayout>
</template>

<script setup>
import SingleMyEventVerificationRequests
    from "@/Pages/EventVerification/Components/SingleMyEventVerificationRequests.vue";
import BasePaginator from "@/Components/Paginate/BasePaginator.vue";
import {onMounted, ref} from "vue";
import BaseAlertComponent from "@/Components/Alerts/BaseAlertComponent.vue";
import {router, usePage} from "@inertiajs/vue3";
import BaseCard from "@/Artwork/Cards/BaseCard.vue";
import CardHeadline from "@/Artwork/Cards/CardHeadline.vue";
import BaseEventVerificationLayout from "@/Pages/EventVerification/BaseEventVerificationLayout.vue";
import PageTitle from "@/Artwork/Titles/PageTitle.vue";
import SinglePlannedEventInVerificationPage
    from "@/Pages/EventVerification/Components/SinglePlannedEventInVerificationPage.vue";


const props = defineProps({
    myRequests: {
        type: Object,
        required: true
    },
    plannedEvents: {
        type: Object,
        required: true
    },
})


const showNewRequestIncoming = ref(false);

onMounted(() => {
    window.Echo.private(`event-verification-index.${usePage().props.auth.user.id}`)
        .listen('.reload-event-verification-requests', (e) => {
            router.reload({
                preserveScroll: true,
                preserveState: false,
                only: ['myRequests'],
                data: {
                    page: 1,
                },
                onSuccess: (e) => {
                    showNewRequestIncoming.value = true;
                    setTimeout(() => {
                        showNewRequestIncoming.value = false;
                    }, 5000);
                }
            })
        });
})
</script>

<style scoped>
</style>
