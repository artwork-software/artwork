<template>
    <tr draggable="true"
        @dragstart="itemDragStart"
        @dragend="itemDragEnd"
        :class="'cursor-grab h-10 ' + trCls">
        <template v-for="(cell) in item.cells">
            <InventoryCell :cell="cell"/>
        </template>
    </tr>
</template>

<script setup>
import InventoryCell from "@/Pages/Inventory/InventoryCell.vue";

const emits = defineEmits(['itemDragging', 'itemDragEnd']);
const props = defineProps({
        index: Number,
        colspan: Number,
        item: Object,
        trCls: String
    }),
    itemDragStart = (e) => {
        emits.call(this,'itemDragging', props.index);

        e.dataTransfer.setData('itemId', props.item.id);
        e.dataTransfer.setData('currentItemIndex', props.index.toString());
    },
    itemDragEnd = () => emits.call(this, 'itemDragEnd')
</script>

<style scoped>
.onDragBackground :deep(td) {
    opacity: 50%;
}
</style>

