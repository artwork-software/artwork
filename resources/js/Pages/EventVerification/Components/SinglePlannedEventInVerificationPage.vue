<template>
    <BaseCard elevation="raised" class="w-full">
        <div class="flex items-stretch gap-x-3 min-w-full w-full h-full p-4">
            <div class="p-1 rounded-lg w-1" :style="{backgroundColor: event.event_type.hex_code}"></div>
            <div class="w-full">
                <p class="text-sm font-lexend font-semibold text-text" :style="{color: event.event_type.hex_code}">
                    {{ event.event_type.abbreviation }}: {{ event.eventName ?? event?.project?.name }}
                </p>
                <p class="mt-1 flex items-center gap-x-1 text-xs text-text-subtle">
                    <span class="font-lexend font-bold">{{ $t('Start') }}:</span>
                    <span class="font-lexend">{{ event?.start_time }}</span>
                    <span class="font-lexend font-bold">{{ $t('End') }}:</span>
                    <span class="font-lexend">{{ event?.end_time }}</span>
                </p>
                <p class="mt-1 flex items-center gap-x-1 text-xs text-text-subtle">
                    <span class="font-lexend font-bold">{{ $t('Room') }}:</span>
                    <span class="font-lexend">{{ event?.room?.name }}</span>
                </p>
                <div class="mt-2 flex items-center justify-end">
                    <BaseUIButton variant="secondary" hide-icon @click="SendEventToVerification">
                        <span class="flex items-center gap-x-2">{{ $t('Request Verification') }}</span>
                    </BaseUIButton>
                </div>
            </div>
        </div>
    </BaseCard>
</template>

<script setup>

import BaseUIButton from "@/Artwork/Buttons/BaseUIButton.vue";
import BaseCard from "@/Artwork/Cards/BaseCard.vue";
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