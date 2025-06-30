<template>
    <WhiteInnerCard>
        <div class="flex items-stretch gap-x-3 min-w-full w-full h-full p-4">
            <div class="p-1 rounded-lg w-1" :class="statuses[eventVerification.status]"></div>
            <div class="w-full">
                <p class="text-sm font-lexend font-semibold text-gray-900" :style="{color: eventVerification?.event?.event_type.hex_code}">
                    {{ eventVerification?.event?.event_type.abbreviation }}: {{ eventVerification?.event?.eventName ?? eventVerification?.event?.project?.name }}
                </p>
                <p class="mt-1 flex items-center gap-x-1 text-xs text-gray-500">
                    <span class="font-lexend font-bold">{{ $t('Start') }}:</span>
                    <span class="font-lexend">{{ eventVerification?.event?.start_time }}</span>
                    <span class="font-lexend font-bold">{{ $t('End') }}:</span>
                    <span class="font-lexend">{{ eventVerification?.event?.end_time }}</span>
                </p>
                <p class="mt-1 flex items-center gap-x-1 text-xs text-gray-500">
                    <span class="font-lexend font-bold">{{ $t('Room') }}:</span>
                    <span class="font-lexend">{{ eventVerification?.event?.room?.name }}</span>
                </p>
                <div class="flex items-center justify-between mt-3">
                    <div>
                        <BaseCardButton text="Open in Calendar" @click="openPlanningCalendarWithEventId">
                            <component is="IconCalendar" class="size-4" aria-hidden="true" />
                        </BaseCardButton>
                    </div>
                    <div>
                        <BaseCardButton text="Approve" @click="approveRequest" v-if="eventVerification.status === 'pending'" class="!bg-green-600 hover:!bg-green-800 capitalize text-xs font-lexend">
                            <component is="IconCheckbox" class="size-4" aria-hidden="true" />
                        </BaseCardButton>
                    </div>
                    <div>
                        <BaseCardButton text="Reject" @click="showRejectEventVerificationRequestModal = true" v-if="eventVerification.status === 'pending'" class="!bg-red-500 hover:!bg-red-800 capitalize text-xs font-lexend">
                            <component is="IconBan" class="size-4" aria-hidden="true" />
                        </BaseCardButton>
                    </div>
                </div>
            </div>
        </div>
    </WhiteInnerCard>
    <!--<td class="py-4 pr-3 pl-4 text-sm font-medium whitespace-nowrap text-gray-900  sm:pl-3">
        <div>
            <div class="flex items-start gap-x-1">
                <component is="IconCalendar" class="size-6 text-gray-400 hover:text-artwork-buttons-create duration-200 ease-in-out cursor-pointer" @click="openPlanningCalendarWithEventId" aria-hidden="true" />
                <p class="text-sm/6 font-semibold text-gray-900">{{ eventVerification?.event?.eventName }}</p>
            </div>
            <p class="mt-1 flex items-center gap-x-1 text-[10px] text-gray-500">
                <span class="font-lexend font-bold">{{ $t('Start') }}:</span>
                <span class="font-lexend">{{ eventVerification?.event?.start_time }}</span>
                <span class="font-lexend font-bold">{{ $t('End') }}:</span>
                <span class="font-lexend">{{ eventVerification?.event?.end_time }}</span>
                <span class="font-lexend font-bold">{{ $t('Room') }}:</span>
                <span class="font-lexend">{{ eventVerification?.event?.room?.name }}</span>
            </p>
        </div>
    </td>
    <td class="px-3 py-4 text-sm whitespace-nowrap text-gray-500">{{ eventVerification.created_at }}</td>
    <td class="px-3 py-4 text-sm whitespace-nowrap text-gray-500">
        <div class="flex items-center gap-x-2">
            <UserPopoverTooltip :user="eventVerification.requester" height="7" width="7" />
            <span class="font-lexend font-bold">
                {{ eventVerification.requester.full_name }}
            </span>
        </div>
    </td>
    <td class="px-3 py-4 text-sm whitespace-nowrap text-gray-500">
        <div class="flex items-center justify-center gap-x-2">
            <div>
                <SmallFormButton @click="approveRequest" v-if="eventVerification.status === 'pending'" class="!bg-green-600 hover:!bg-green-800 capitalize text-xs font-lexend">
                    <component is="IconCheckbox" class="size-4" aria-hidden="true" />
                </SmallFormButton>
            </div>
            <div>
                <SmallFormButton @click="showRejectEventVerificationRequestModal = true" v-if="eventVerification.status === 'pending'" class="!bg-red-500 hover:!bg-red-800 capitalize text-xs font-lexend">
                    <component is="IconBan" class="size-4" aria-hidden="true" />
                </SmallFormButton>
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

import SmallFormButton from "@/Components/Buttons/SmallFormButton.vue";
import {router, usePage} from "@inertiajs/vue3";
import UserPopoverTooltip from "@/Layouts/Components/UserPopoverTooltip.vue";
import {defineAsyncComponent, ref} from "vue";
import WhiteInnerCard from "@/Artwork/Cards/WhiteInnerCard.vue";
import BaseCardButton from "@/Artwork/Buttons/BaseCardButton.vue";

const props = defineProps({
    eventVerification: {
        type: Object,
        required: true
    }
})

const statuses = {
    approved: 'text-green-700 bg-green-500 ring-green-600/20',
    pending: 'text-gray-600 bg-gray-500 ring-gray-500/10',
    rejected: 'text-red-800 bg-red-500 ring-red-600/20',
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

const openPlanningCalendarWithEventId = () => {
    // Get the event's start date
    const eventStartDate = new Date(props.eventVerification.event.start_time);

    // Calculate the start of the week (Monday)
    const startOfWeek = new Date(eventStartDate);
    startOfWeek.setDate(eventStartDate.getDate() - eventStartDate.getDay() + (eventStartDate.getDay() === 0 ? -6 : 1));

    // Calculate the end of the week (Sunday)
    const endOfWeek = new Date(startOfWeek);
    endOfWeek.setDate(startOfWeek.getDate() + 6);

    // Format dates as YYYY-MM-DD
    const formatDate = (date) => {
        const year = date.getFullYear();
        const month = String(date.getMonth() + 1).padStart(2, '0');
        const day = String(date.getDate()).padStart(2, '0');
        return `${year}-${month}-${day}`;
    };

    const startDate = formatDate(startOfWeek);
    const endDate = formatDate(endOfWeek);

    // Update the user's calendar filter to show the week containing the event
    router.patch(route('update.user.calendar.filter.dates', usePage().props.auth.user.id), {
        start_date: startDate,
        end_date: endDate,
    }, {
        preserveScroll: true,
        preserveState: false,
        onSuccess: () => {
            // After updating the filter, navigate to the calendar with the highlighted event
            router.visit(route('planning-event-calendar.index', {highlightEventId: props.eventVerification.event.id}), {
                preserveScroll: true,
                preserveState: false,
            });
        }
    });
}


</script>

<style scoped>

</style>
