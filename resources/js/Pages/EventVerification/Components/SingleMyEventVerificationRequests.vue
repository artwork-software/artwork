<template>
    <WhiteInnerCard>
        <div class="flex items-stretch gap-x-3 min-w-full w-full h-full">
            <div class="p-1 rounded-lg w-1"
                 :class="{
         'bg-green-500': myRequest.status_color === 'green',
         'bg-yellow-400': myRequest.status_color === 'yellow',
         'bg-red-500': myRequest.status_color === 'red',
         'bg-gray-300': myRequest.status_color === 'gray'
     }"
            ></div>
            <div class="w-full">
                <p class="text-sm font-lexend font-semibold text-gray-900" :style="{color: myRequest.event_type.hex_code}">
                    {{ myRequest.event_type.abbreviation }}: {{ myRequest.eventName ?? myRequest?.project?.name }}
                </p>
                <p class="mt-1 flex items-center gap-x-1 text-xs text-gray-500">
                    <span class="font-lexend font-bold">{{ $t('Start') }}:</span>
                    <span class="font-lexend">{{ myRequest?.start_time }}</span>
                    <span class="font-lexend font-bold">{{ $t('End') }}:</span>
                    <span class="font-lexend">{{ myRequest?.end_time }}</span>
                </p>
                <p class="mt-1 flex items-center gap-x-1 text-xs text-gray-500">
                    <span class="font-lexend font-bold">{{ $t('Room') }}:</span>
                    <span class="font-lexend">{{ myRequest?.room?.name }}</span>
                </p>
                <!-- PROGRESSBAR -->
                <div class="mt-3 pb-3 hidden">
                    <div class="relative w-full bg-gray-200 rounded-lg h-2.5 overflow-hidden flex">
                        <div class="h-full bg-gradient-to-r from-green-400 to-emerald-500" :style="{width: myRequest.verification_status_percentages.approved + '%'}"></div>
                        <div class="h-full bg-gradient-to-r from-orange-500 to-red-600" :style="{width: myRequest.verification_status_percentages.rejected + '%'}"></div>
                    </div>
                </div>
                <!-- VERIFIER ANZEIGE -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-4">
                    <div v-for="(verification, key) in myRequest.verifier_grouped_by_status" :key="key">
                        <p class="text-xs font-lexend font-semibold text-gray-900">
                            {{ $t(key) }}:
                        </p>
                        <div class="flex -space-x-1 mt-1">
                            <div v-for="verifier in verification" :key="verifier.id">
                                <UserPopoverTooltip
                                    isWhite
                                    classes="!ring-2 !ring-white"
                                    :user="verifier"
                                    height="8"
                                    width="8"
                                    class=""
                                />
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </WhiteInnerCard>
</template>

<script setup>

import UserPopoverTooltip from "@/Layouts/Components/UserPopoverTooltip.vue";
import WhiteInnerCard from "@/Artwork/Cards/WhiteInnerCard.vue";

const props = defineProps({
    myRequest: {
        type: Object,
        required: true
    },
})



</script>

<style scoped>

</style>
