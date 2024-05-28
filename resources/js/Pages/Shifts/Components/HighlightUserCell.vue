
<template>
    <div :style="divStyle" @click="$emit('highlightShiftsOfUser', item.id, type)" class="w-full p-2 my-2 bg-gray-50/10 text-white text-xs rounded-lg flex items-center gap-2">
        <div>
            <img :src="item.profile_photo_url" alt="" class="h-6 w-6 rounded-full object-cover">
        </div>
        <div class="text-left h-8 cursor-pointer">
            <div v-if="type === 0" class="text-ellipsis w-32">
                <div class="flex">
                <div class="truncate">
                    {{ item.first_name }} {{ item.last_name }}
                </div>
                <div class="ml-1">(i)</div>
                </div>
                <div class="text-xs w-full flex"> {{plannedHours.toFixed(1)}}  {{expectedHours ? ' | ' + expectedHours.toFixed(1) : ''}}</div>
            </div>
            <div v-else-if="type === 1" class="text-ellipsis w-32">
                <div class="flex">
                    <div class="truncate">
                        {{ item.first_name }} {{ item.last_name }}
                    </div>
                    <div class="ml-1"> (e) </div>
                </div>
                <div class="text-xs w-full">{{plannedHours.toFixed(1)}}</div>
            </div>
            <div v-else class="text-ellipsis w-32">
                <div class="flex">
                    <div class="truncate">
                {{ item.provider_name }}</div>
                    <div class="ml-1"> (DL) </div>
                </div>
                <div class="text-xs w-full">{{plannedHours.toFixed(1)}}</div>
            </div>
        </div>
    </div>
</template>
<script>
import {defineComponent} from 'vue'

export default defineComponent({
    name: "HighlightUserCell",
    props: ['item', 'type','plannedHours','expectedHours','highlightedUser'],
    emits: ['highlightShiftsOfUser'],
    methods: {

    },
    computed: {
        divStyle() {
            return {
                opacity: this.highlightedUser ? '1' : '0.3'
            };
        }
    }
})
</script>
<style scoped>
.truncate {
    overflow: hidden;
    white-space: nowrap;
    text-overflow: ellipsis;
}
</style>
