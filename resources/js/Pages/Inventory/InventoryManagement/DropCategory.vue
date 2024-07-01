<template>
    <tr draggable="true"
        @dragover="categoryDragOver"
        @dragleave="categoryDragLeave"
        @drop="categoryDrop"
        :class="dragOverClass">
        <td :colspan="colspan">
            <div class="flex flex-row h-8 border border-dashed border-blue-700 justify-center items-center">
                <IconDragDrop class="w-5 h-5"/>
                <span class="text-xs subpixel-antialiased">{{ $t('Place here') }}</span>
            </div>
        </td>
    </tr>
    <tr>
        <td :colspan="colspan" class="h-0.5"/>
    </tr>
</template>
<script setup>
import {IconDragDrop} from "@tabler/icons-vue";
import {computed, ref} from "vue";

const emits = defineEmits(['categoryRequestsDragMove']),
    props = defineProps({
        colspan: Number,
        destinationIndex: Number
    }),
    draggedOver = ref(false),
    dragOverClass = computed(() => {
        return draggedOver.value ? 'bg-primary text-white' : 'text-black';
    }),
    categoryDragOver = (e) => {
        draggedOver.value = true;
        e.preventDefault()
    },
    categoryDragLeave = () => {
        draggedOver.value = false;
    },
    categoryDrop = (e) => {
        emits.call(
            this,
            'categoryRequestsDragMove',
            e.dataTransfer.getData('categoryId'),
            props.destinationIndex
        );
    };
</script>
