<template>
    <tr :draggable="isDraggable"
        @dragstart="itemDragStart"
        @dragend="itemDragEnd"
        :class="'cursor-grab h-10 ' + trCls">
        <template v-for="(cell) in item.cells">
            <InventoryCell :cell="cell"
                           @is-editing-cell-value="handleCellIsEditing"/>
        </template>
    </tr>
</template>

<script setup>
import InventoryCell from "@/Pages/Inventory/InventoryItemCell.vue";
import {computed, ref} from "vue";

const emits = defineEmits(['itemDragging', 'itemDragEnd']),
    props = defineProps({
        index: Number,
        colspan: Number,
        item: Object,
        trCls: String
    }),
    inventoryCellIsEditing = ref(false),
    isDraggable = computed(() => {
        return !inventoryCellIsEditing.value;
    }),
    handleCellIsEditing = (isEditing) => {
        inventoryCellIsEditing.value = isEditing;
    },
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

