
<template>
    <div :class="[$page.props.user.compact_mode ? 'h-8 flex items-center justify-between' : 'h-12']" class="drag-item w-full p-2 text-white text-xs rounded-lg flex items-center gap-2" draggable="true" @dragstart="onDragStart"  :style="{backgroundColor: backgroundColorWithOpacity(color)}">
        <div class="text-white" v-if="!$page.props.user.compact_mode">
            <img :src="item.profile_photo_url" alt="" class="h-6 w-6 rounded-full object-cover min-w-6 min-h-6">
        </div>
        <div class="text-left cursor-pointer" >
            <div v-if="type === 0" class="text-ellipsis" :class="$page.props.user.compact_mode ? 'w-36' : 'w-28'">
                <div class="flex">
                    <div class="truncate">
                        {{ item.first_name }} {{ item.last_name }}
                    </div>
                </div>
                <div class="text-xs w-full flex"  v-if="!$page.props.user.compact_mode"> {{plannedHours.toFixed(1)}}  {{expectedHours ? ' | ' + expectedHours.toFixed(1) : ''}}</div>
            </div>
            <div v-else-if="type === 1" class="text-ellipsis" :class="$page.props.user.compact_mode ? 'w-36' : 'w-28'">
                <div class="flex">
                    <div class="truncate">
                        {{ item.first_name }} {{ item.last_name }}
                    </div>
                </div>
                <div class="text-xs w-full"  v-if="!$page.props.user.compact_mode">{{plannedHours.toFixed(1)}}</div>
            </div>
            <div v-else class="text-ellipsis" :class="$page.props.user.compact_mode ? 'w-36' : 'w-28'">
                <div class="flex">
                    <div class="truncate">
                        {{ item.provider_name }}</div>
                </div>
                <div class="text-xs w-full"  v-if="!$page.props.user.compact_mode">{{plannedHours.toFixed(1)}}</div>
            </div>
        </div>
        <a :style="{color: TextColorWithDarken(color, 10)}" v-if="type === 0" :href="route('user.edit.shiftplan', item.id)">
            <IconCalendarShare class="h-5 w-5" />
        </a>
    </div>



</template>
<script>
import {defineComponent} from 'vue'
import ColorHelper from "@/Mixins/ColorHelper.vue";
import IconLib from "@/Mixins/IconLib.vue";

export default defineComponent({
    name: "DragElement",
    mixins: [ColorHelper, IconLib],
    props: ['item', 'type', 'plannedHours', 'expectedHours', 'color', 'craft'],
    methods: {
        onDragStart(event) {
            event.dataTransfer.setData(
                'application/json',
                JSON.stringify(
                    {
                        id: this.item.id,
                        type: this.type,
                        craft_ids: this.item.assigned_craft_ids,
                        shift_qualifications: this.item.shift_qualifications,
                        craft_universally_applicable: this?.craft?.universally_applicable ?? false,
                        craft_abbreviation: this.craft.abbreviation
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
