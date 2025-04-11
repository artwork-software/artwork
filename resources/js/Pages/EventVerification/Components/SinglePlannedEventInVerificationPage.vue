<template>
    <WhiteInnerCard>
        <div class="flex items-stretch gap-x-3 min-w-full w-full h-full">
            <div class="p-1 rounded-lg w-1" :style="{backgroundColor: event.event_type.hex_code}"></div>
            <div class="w-full">
                <p class="text-sm font-lexend font-semibold text-gray-900" :style="{color: event.event_type.hex_code}">
                    {{ event.event_type.abbreviation }}: {{ event.eventName }}
                </p>
                <p class="mt-1 flex items-center gap-x-1 text-xs text-gray-500">
                    <span class="font-lexend font-bold">{{ $t('Start') }}:</span>
                    <span class="font-lexend">{{ event?.start_time }}</span>
                    <span class="font-lexend font-bold">{{ $t('End') }}:</span>
                    <span class="font-lexend">{{ event?.end_time }}</span>
                </p>
                <p class="mt-1 flex items-center gap-x-1 text-xs text-gray-500">
                    <span class="font-lexend font-bold">{{ $t('Room') }}:</span>
                    <span class="font-lexend">{{ event?.room?.name }}</span>
                </p>
                <div class="mt-2 flex items-center justify-end">
                    <BaseCardButton text="Request Verification" @click="SendEventToVerification" />
                </div>
            </div>
        </div>
    </WhiteInnerCard>
</template>

<script setup>

import BaseCardButton from "@/Artwork/Buttons/BaseCardButton.vue";
import WhiteInnerCard from "@/Artwork/Cards/WhiteInnerCard.vue";
import {router} from "@inertiajs/vue3";

const props = defineProps({
    event: {
        type: Object,
        required: true,
    }
})


const SendEventToVerification = () => {
    router.post(route('events.sendToVerification', {event: props.event.id}), {
        preserveScroll: true,
        preserveState: true,
        onSuccess: () => {

        }
    });
}
</script>

<style scoped>

</style>