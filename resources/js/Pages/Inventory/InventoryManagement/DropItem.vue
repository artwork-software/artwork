<template>

    <tr draggable="true"
        @dragover="itemDragOver"
        @dragleave="itemDragLeave"
        @drop="itemDrop"
        :class="dragOverClass">
        <td :colspan="colspan">
            <div class="drop-item-container">
                <IconDragDrop class="icon"/>
                <span class="text">{{ $t('Place here') }}</span>
            </div>
        </td>
    </tr>
    <tr v-if="destinationIndex !== maxIndex">
        <td class="empty-row-xxs-td"></td>
    </tr>
</template>
<script setup>
import {IconDragDrop} from "@tabler/icons-vue";
import {computed, ref} from "vue";

const emits = defineEmits(['itemRequestsDragMove']),
    props = defineProps({
        colspan: Number,
        destinationIndex: Number,
        maxIndex: Number
    }),
    draggedOver = ref(false),
    dragOverClass = computed(() => {
        return draggedOver.value ? 'bg-primary text-white' : 'text-black';
    }),
    itemDragOver = (e) => {
        draggedOver.value = true;
        e.preventDefault()
    },
    itemDragLeave = () => {
        draggedOver.value = false;
    },
    itemDrop = (e) => {
        emits.call(
            this,
            'itemRequestsDragMove',
            e.dataTransfer.getData('itemId'),
            e.dataTransfer.getData('currentItemIndex'),
            props.destinationIndex
        );
    };
</script>
