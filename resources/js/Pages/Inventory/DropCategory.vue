<template>
    <tr draggable="true" @dragover="categoryDragOver" @dragleave="categoryDragLeave" @drop="categoryDrop" :class="dragOverClass">
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

const props = defineProps({
        colspan: Number
    }),
    draggedOver = ref(false),
    dragOverClass = computed(() => {
        return draggedOver.value ? 'bg-primary text-white' : 'text-black';
    }),
    categoryDragOver = (e) => {
        draggedOver.value = true;
        e.preventDefault()
    },
    categoryDragLeave = (e) => {
        draggedOver.value = false;
    },
    categoryDrop = (e) => {
        console.debug('categoryDropped', e);
    };
</script>
