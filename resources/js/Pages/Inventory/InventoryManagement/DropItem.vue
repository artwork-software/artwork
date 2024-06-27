<template>
    <tr draggable="true"
        @dragover="itemDragOver"
        @dragleave="itemDragLeave"
        @drop="itemDrop"
        :class="dragOverClass">
        <td :colspan="colspan">
            <div class="flex flex-row py-1 border border-dashed border-blue-700 justify-center items-center">
                <IconDragDrop class="w-5 h-5"/>
                <span class="text-xs subpixel-antialiased">Hier platzieren</span>
            </div>
        </td>
    </tr>
</template>
<script setup>
import {IconDragDrop} from "@tabler/icons-vue";
import {computed, ref} from "vue";

const emits = defineEmits(['itemRequestsDragMove']),
    props = defineProps({
        colspan: Number,
        destinationIndex: Number
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
