
<template>
    <div class="drag-item w-full p-2 text-white text-xs rounded-lg flex items-center gap-2" draggable="true" @dragstart="onDragStart"  :style="{backgroundColor: backgroundColorWithOpacity(color), color: TextColorWithDarken(color, 10)}">
        <div class="" v-if="!$page.props.user.compact_mode">
            <img :src="item.profile_photo_url" alt="" class="h-6 w-6 rounded-full object-cover">
        </div>
        <div class="text-left cursor-pointer" :class="[$page.props.user.compact_mode ? 'h-4' : 'h-8']">
            <div v-if="type === 0" class="text-ellipsis w-32">
                <div class="flex">
                <div class="truncate">
                    {{ item.first_name }} {{ item.last_name }}
                </div>
                <div class="ml-1">(i)</div>
                </div>
                <div class="text-xs w-full flex"  v-if="!$page.props.user.compact_mode"> {{plannedHours.toFixed(1)}}  {{expectedHours ? ' | ' + expectedHours.toFixed(1) : ''}}</div>
            </div>
            <div v-else-if="type === 1" class="text-ellipsis w-32">
                <div class="flex">
                    <div class="truncate">
                        {{ item.first_name }} {{ item.last_name }}
                    </div>
                    <div class="ml-1"> (e) </div>
                </div>
                <div class="text-xs w-full"  v-if="!$page.props.user.compact_mode">{{plannedHours.toFixed(1)}}</div>
            </div>
            <div v-else class="text-ellipsis w-32">
                <div class="flex">
                    <div class="truncate">
                        {{ item.provider_name }}</div>
                    <div class="ml-1"> (DL) </div>
                </div>
                <div class="text-xs w-full"  v-if="!$page.props.user.compact_mode">{{plannedHours.toFixed(1)}}</div>
            </div>
        </div>
    </div>

</template>
<script>
import {defineComponent} from 'vue'
import ColorHelper from "@/Mixins/ColorHelper.vue";

export default defineComponent({
    name: "DragElement",
    mixins: [ColorHelper],
    props: ['item', 'type', 'plannedHours', 'expectedHours', 'color'],
    methods: {
        onDragStart(event) {
            event.dataTransfer.setData(
                'application/json',
                JSON.stringify(
                    {
                        id: this.item.id,
                        type: this.type,
                        craft_ids: this.item.assigned_craft_ids,
                        shift_qualifications: this.item.shift_qualifications
                    }
                )
            );
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
