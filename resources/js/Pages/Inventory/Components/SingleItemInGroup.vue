<template>
    <tr class="w-full flex">
        <th class="stickyYAxisNoMarginLeft bg-artwork-navigation-background flex items-center text-right w-48">
            <ItemDragElement :multi-edit="multiEdit" :item="item" />
        </th>
        <td v-for="day in days" class="flex gap-x-0.5 relative">
            <div class="p-2 bg-gray-50/10 text-white text-xs max-h-[48px] rounded-lg shiftCell cursor-pointer relative overflow-y-scroll" @click="showItemDetailModal(day)" :style="{width: '198px'}" >
                <div v-for="event in item.events">
                    <div v-if="event.period.includes(day.full_day)" class="flex items-center justify-between gap-x-1 mb-0.5">
                        <div class="truncate w-1/2">
                            {{ event.eventInfo.name ?? event.eventInfo.project_name }}
                        </div>
                        <div class="stock-badge bg-gray-300/30">
                            {{ event.quantity }}
                            <span v-if="event.overbooked > 0" class="text-red-300">
                                / {{ event.overbooked }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </td>
    </tr>

    <ItemCellDetailModal :item="item" :day="dayToShow" v-if="showItemDetail" @closed="showItemDetail = false" />
</template>

<script setup>

import ItemDragElement from "@/Pages/Inventory/Components/ItemDragElement.vue";
import ItemCellDetailModal from "@/Pages/Inventory/Components/ItemCellDetailModal.vue";
import {ref} from "vue";

const props = defineProps({
    item: {
        type: Object,
        required: true
    },
    days: {
        type: Object,
        required: true
    },
    multiEdit: {
        type: Boolean,
        required: false,
        default: false
    }
})

const showItemDetail = ref(false);
const dayToShow = ref(null);

const showItemDetailModal = (day) => {
    dayToShow.value = day;
    showItemDetail.value = true;
}

</script>

<style scoped>
.stickyYAxisNoMarginLeft {
    position: sticky;
    align-self: flex-start;
    position: -webkit-sticky;
    left: 0;
    z-index: 22;
}
</style>
