<template>
    <WhiteInnerCard>
        <div class="flex items-stretch gap-x-3 min-w-full w-full h-full px-4 pt-4 pb-2">
            <!-- STATUS ANZEIGE -->
            <div class="p-1 rounded-lg w-1"
                 :class="{'bg-green-500': myRequest.status_color === 'green',
                          'bg-yellow-400': myRequest.status_color === 'yellow',
                          'bg-red-500': myRequest.status_color === 'red',
                          'bg-gray-300': myRequest.status_color === 'gray'
                          }"
            ></div>
            <!-- INFORMATIONEN ANZEIGE -->
            <div class="w-full">
                <p class="text-sm font-lexend font-semibold text-gray-900"
                   :style="{color: myRequest.event_type.hex_code}">
                    {{ myRequest.event_type.name }}: {{ myRequest.eventName ?? myRequest?.project?.name }}
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
            </div>
        </div>
        <DashedDivider class="mt-2"></DashedDivider>
        <!-- VERIFIER ANZEIGE -->
        <div class="grid grid-cols-1 md:grid-cols-3 -mx-0.5 -mb-0.5">
            <div :class="{'background-color soft-green border-color soft-green border-b-2 border-l-2  rounded-bl-lg': key === 'approved',
                          'background-color soft-yellow border-color border-b-2  rounded-br-lg border-r-2': key === 'pending',
                          'background-color soft-red border-color border-x-2 border-borderRedSoft border-b-2': key === 'rejected',
                          }"  class="p-2" v-for="(verification, key) in myRequest.verifier_grouped_by_status" :key="key">
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
    </WhiteInnerCard>
</template>

<script setup>

import UserPopoverTooltip from "@/Layouts/Components/UserPopoverTooltip.vue";
import WhiteInnerCard from "@/Artwork/Cards/WhiteInnerCard.vue";
import DashedDivider from "@/Artwork/Divider/DashedDivider.vue";

const props = defineProps({
    myRequest: {
        type: Object,
        required: true
    },
})


</script>

<style scoped>

</style>
